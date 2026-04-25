<?php
require_once dirname(__FILE__) . "/../bootstrap.php";

$where = ["1=1"];
$errors = [];

$state_id = isset($_GET['state_id']) ? intval($_GET['state_id']) : 0;

$county_name = isset($_GET['county_name']) ? trim($_GET['county_name']) : '';

$income_min = isset($_GET['income_min']) ? $_GET['income_min'] : '';
$income_max = isset($_GET['income_max']) ? $_GET['income_max'] : '';

$edu_min = isset($_GET['edu_min']) ? $_GET['edu_min'] : '';

$gender = isset($_GET['gender']) ? $_GET['gender'] : '';

$race = isset($_GET['race']) ? $_GET['race'] : '';
$race_pct = isset($_GET['race_pct']) ? $_GET['race_pct'] : '10';

$occupation = isset($_GET['occupation']) ? $_GET['occupation'] : '';

$age_min = isset($_GET['age_min']) ? $_GET['age_min'] : '';
$age_max = isset($_GET['age_max']) ? $_GET['age_max'] : '';

$affordability = isset($_GET['affordability']) ? $_GET['affordability'] : '';

//Validation for inputs
if ($income_min !== '' && !is_numeric($income_min) || $income_min < 0) {
    $errors[] = "Income Min must be a positive number.";
}
if ($income_max !== '' && !is_numeric($income_max) || $income_max < 0) {
    $errors[] = "Income Max must be a positive number.";
}
if ($income_min !== '' && $income_max !== '' && $income_min > $income_max) {
    $errors[] = "Income Min cannot be greater than Income Max.";
}
if ($edu_min !== '' && !is_numeric($edu_min) || $edu_min < 0 || $edu_min > 100) {
    $errors[] = "Education percentage must be a number between 0 and 100.";
}
if ($age_min !== '' && !is_numeric($age_min) || $age_min < 0) {
    $errors[] = "Age Min must be a positive number.";
}
if ($age_max !== '' && !is_numeric($age_max) || $age_max < 0) {
    $errors[] = "Age Max must be a positive number.";
}
if ($age_min !== '' && $age_max !== '' && $age_min > $age_max) {
    $errors[] = "Age Min cannot be greater than Age Max.";
}

//Query construction if no validation errors
$results = [];
$searched = isset($_GET['search']);
$result_count = 0;

if ($searched && empty($errors)) {
    if ($state_id > 0) {
        $where[] = "l.state_id = " . intval($state_id);
    }
    if ($county_name !== '') {
        $where[] = "l.county_name LIKE '%" . $database->escape_string($county_name) . "%'";
    }
    if ($income_min !== '') {
        $where[] = "i.Median_Household_Income >= " . floatval($income_min);
    }
    if ($income_max !== '') {
        $where[] = "i.Median_Household_Income <= " . floatval($income_max);
    }
    if ($edu_min !== '') {
        $where[] = "ed.Pct_Any_Degree >= " . (floatval($edu_min) / 100);
    }
    if ($gender === 'majority_female') $where[] = "g.Pct_Female > 0.50";
    if ($gender === 'majority_male') $where[] = "g.Pct_Male > 0.50";

    $race_map = [
        'white' => 'Pct_White',
        'black' => 'Pct_Black',
        'hispanic' => 'Pct_Hispanic',
        'asian' => 'Pct_Asian'];
    if ($race !== '' && isset($race_map[$race]))
        $where [] = $race_map[$race] . " >= " . (floatval($race_pct) / 100);

    $occ_map = [
        'management' => 'o.Pct_Management',
        'service' => 'o.Pct_Service',
        'sales' => 'o.Pct_Sales',
        'naturalres' => 'o.Pct_NaturalRes',
        'production' => 'o.Pct_Production'];
    if ($occupation !== '' && isset($occ_map[$occupation]))
        $where [] = $occ_map[$occupation] . " = (SELECT GREATEST(o2.Pct_Management, o2.Pct_Service, o2.Pct_Sales, o2.Pct_NaturalRes, o2.Pct_Production) FROM occupation o2 WHERE o2.county_id = l.county_id)";

    if ($age_min !== '') $where[] = "a2.Median_Age >= " . floatval($age_min);
    if ($age_max !== '') $where[] = "a2.Median_Age <= " . floatval($age_max);

    $allowed_tiers = ['Affordable', 'Moderate', 'Expensive', 'Extreme'];
    if ($affordability !== '' && in_array($affordability, $allowed_tiers))
        $where[] = "cb.Affordability_Tier = '" . $database->escape_string($affordability) . "'";

    $where_sql = implode(" AND ", $where);

    $results = $database->search_db($where_sql);
    $result_count = count($results);
}

$states_result = $database->states_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="apple-touch-icon" sizes="180x180" href="/favicon_io/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/favicon_io/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/favicon_io/favicon-16x16.png">
  <link rel="manifest" href="/favicon_io/site.webmanifest">
  
  <title>Search - SocioEconomic Insights</title>
    <link rel="stylesheet" href="css/style.css">
    <script src = "https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
</head>
<body>
<?php include dirname(__FILE__) . '/includes/navigation.php' ?>

<div class="page" style="width: 50%">
    <div class="page-header">
        <h1>County Explorer</h1>
        <p>Filter counties across the U.S. by income, education, demographics, occupation, and cost of living.</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div style="background:var(--danger-dim);border:1px solid var(--danger);border-radius:var(--radius);padding:12px 12px;margin-bottom:1.5rem">
            <?php foreach ($errors as $e): ?>
                <div style="font-size:13px;color:var(--danger)"><?= htmlspecialchars($e); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card" style="margin-bottom:1.5rem;">
        <form method="GET" action="search.php">
            <p class="section-label">Location</p>
            <div class="filter-group">
                <label>State</label>
                <select name="state_id">
                    <option value="">All states</option>
                    <?php while ($s = $states_result->fetch_assoc()): ?>
                        <option value="<?= $s['state_id']; ?>" <?= $state_id == $s['state_id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($s['State_Name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>County Name Contains</label>
                <input type="text" name="county_name" value="<?= htmlspecialchars($county_name); ?>" placeholder="e.g. 'Olmsted'">
            </div>
        </div>

        <p class="section-label">Income &amp; Education</p>
        <div class="filter-grid" style="margin-bottom:1rem">
            <div class="filter-group">
                <label>Minimum household income ($)</label>
                <input type="number" name="income_min" value="<?= htmlspecialchars($income_min); ?>" placeholder="e.g. 50000" min="0"
                class="<?= (in_array('Minimum income must be a positive integer.', $errors) || in_array('Minimum income cannot be greater than maximum income.', $errors)) ? 'invalid' : ''; ?>">
            </div>
            <div class="filter-group">
                <label>Maximum household income ($)</label>
                <input type="number" name="income_max" value="<?= htmlspecialchars($income_max); ?>" placeholder="e.g. 100000" min="0"
                class="<?= (in_array('Maximum income must be a positive integer.', $errors) || in_array('Maximum income cannot be less than minimum income.', $errors)) ? 'invalid' : ''; ?>">
            </div>
            <div class="filter-group">
                <label>Minimum % with any degree</label>
                <input type="number" name="edu_min" value="<?= htmlspecialchars($edu_min); ?>" placeholder="e.g. 40" min="0" max="100"
                class="<?= in_array('Education percentage must be a number between 0 and 100.', $errors) ? 'invalid' : ''; ?>">
            </div>
        </div>

        <p class="section-label">Demographics</p>
        <div class="filter-grid" style="margin-bottom:1rem">
            <div class="filter-group">
                <label>Gender Majority</label>
                <select name="gender">
                    <option value="">Any</option>
                    <option value="majority_female" <?= $gender === 'majority_female' ? 'selected' : ''; ?>>Majority female</option>
                    <option value="majority_male" <?= $gender === 'majority_male' ? 'selected' : ''; ?>>Majority male</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Race / Ethnicity</label>
                <select name="race">
                    <option value="">Any</option>
                    <option value="white" <?= $race === 'white' ? 'selected' : ''; ?>>White</option>
                    <option value="black" <?= $race === 'black' ? 'selected' : ''; ?>>Black</option>
                    <option value="hispanic" <?= $race === 'hispanic' ? 'selected' : ''; ?>>Hispanic</option>
                    <option value="asian" <?= $race === 'asian' ? 'selected' : ''; ?>>Asian</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Minimum % of selected race</label>
                <input type="number" name="race_pct" value="<?= htmlspecialchars($race_pct); ?>" placeholder="e.g. 20" min="0" max="100"
                class="<?= in_array('Race percentage must be a number between 0 and 100.', $errors) ? 'invalid' : ''; ?>">
            </div>
            <div class="filter-group">
                <label>Minimum median age</label>
                <input type="number" name="age_min" value="<?= htmlspecialchars($age_min); ?>" placeholder="e.g. 30" min="0"
                class="<?= (in_array('Minimum age must be a positive number.', $errors) || in_array('Age cannot be negative.', $errors)) ? 'invalid' : ''; ?>">
            </div>
            <div class="filter-group">
                <label>Maximum median age</label>
                <input type="number" name="age_max" value="<?= htmlspecialchars($age_max); ?>" placeholder="e.g. 50" min="0"
                class="<?= (in_array('Maximum age must be a positive number.', $errors) || in_array('Age cannot be negative.', $errors)) ? 'invalid' : ''; ?>">
            </div>
        </div>

        <p class="section-label"> Occupation &amp; housing</p>
        <div class="filter-grid" style="margin-bottom:1rem">
            <div class="filter-group">
                <label>Majority occupation</label>
                <select name="occupation">
                    <option value="">Any</option>
                    <option value="management" <?= $occupation === 'management' ? 'selected' : ''; ?>>Management</option>
                    <option value="service" <?= $occupation === 'service' ? 'selected' : ''; ?>>Service</option>
                    <option value="sales" <?= $occupation === 'sales' ? 'selected' : ''; ?>>Sales</option>
                    <option value="naturalres" <?= $occupation === 'naturalres' ? 'selected' : ''; ?>>Natural Resources</option>
                    <option value="production" <?= $occupation === 'production' ? 'selected' : ''; ?>>Production</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Housing affordability tier</label>
                <select name="affordability">
                    <option value="">Any</option>
                    <option value="Affordable" <?= $affordability === 'Affordable' ? 'selected' : ''; ?>>Affordable</option>
                    <option value="Moderate" <?= $affordability === 'Moderate' ? 'selected' : ''; ?>>Moderate</option>
                    <option value="Expensive" <?= $affordability === 'Expensive' ? 'selected' : ''; ?>>Expensive</option>
                    <option value="Extreme" <?= $affordability === 'Extreme' ? 'selected' : ''; ?>>Extreme</option>
                </select>
            </div>
        </div>
        <div class="btn-row">
            <button type="submit" name="search" class="btn btn-primary">Search</button>
            <a href="search.php" class="btn btn-outline">Reset</a>
            <?php if ($session->is_logged_in() && $searched && empty($errors)): ?> 
            <a href="./includes/export.php"><button type="button" class="btn btn-primary">Export</button></a>
            <?php endif; ?>
            <?php if ($searched): ?>
                <span style="font-size:12px;color:var(--text3);font-family:var(--mono);margin-left:auto"><?= $result_count; ?> result<?= $result_count !== 1 ? 's' : ''; ?></span>
            <?php endif; ?>
        </div>
        </form>
    <?php $header = [['County', 'State', 'Median Income', '% with Degree', 'Unemployment Rate', 'Median Rent', 'Affordability Tier', 'Median Age', 'Opportunity Score']]; ?>
    <?php if ($searched && empty($errors)): ?>
        <div class="card" style="padding:0;overflow:hidden">
            <?php if ($result_count > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>County</th>
                        <th>State</th>
                        <th>Median Income</th>
                        <th>% with Degree</th>
                        <th>Unemployment Rate</th>
                        <th>Median Rent</th>
                        <th>Affordability Tier</th>
                        <th>Median Age</th>
                        <th>Opportunity Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $data = []; ?>
                    <?php foreach ($results as $r): ?>
                    <?php $data[] = [$r['County_Name'], $r['State_Name'], number_format($r['Median_Household_Income']), round($r['Pct_Any_Degree'] * 100, 1), $r['Unemployment_Rate_Pct'], number_format($r['Median_Gross_Rent']), $r['Affordability_Tier'], round($r['Median_Age']), round($r['Composite_Opportunity_Score'] * 100, 1)]; ?>
                        <tr onclick="window.location='county.php?county_id=<?= $r['county_id']; ?>'" style="cursor:pointer">
                            <td><strong><?= htmlspecialchars($r['County_Name']); ?></strong></td>
                            <td><?= htmlspecialchars($r['State_Name']); ?></td>
                            <td style="font-family:var(--mono);font-size:12px"><?= number_format($r['Median_Household_Income']); ?></td>
                            <td style="font-family:var(--mono);font-size:12px"><?= round($r['Pct_Any_Degree'] * 100, 1); ?>%</td>
                            <td style="font-family:var(--mono);font-size:12px"><?= $r['Unemployment_Rate_Pct']; ?>%</td>
                            <td style="font-family:var(--mono);font-size:12px">$<?= number_format($r['Median_Gross_Rent']); ?></td>
                            <td><span class="tier-badge tier-<?= strtolower($r['Affordability_Tier']); ?>"><?= $r['Affordability_Tier']; ?></span></td>
                            <td style="font-family:var(--mono);font-size:12px"><?= round($r['Median_Age'], 1); ?></td>
                            <td><span class="score-val"><?= round($r['Composite_Opportunity_Score'] * 100, 1); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php $report->set_report('SearchReport', $header, $data, 'A1:I' . count($data) + 1); ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <span class="icon">&#128269;</span>
            <p>No counties match the chosen filters. Please adjust your search criteria and try again.</p>
        </div>
        <?php endif; ?>
    </div>
    <?php elseif (!$searched): ?>
        <div class="card">
            <div class="empty-state">
                <span class="icon">&#128269;</span>
                <p>Use the filters above to search for counties that match your criteria.</p>
            </div>
    </div>
    <?php endif; ?>
    </div>
</body>
</html>