<?php
include "db_connection.php";

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
$result_count = 0;
$searched = isset($_GET['search']);

if ($searched && empty($errors)) {
    if ($state_id > 0) {
        $where[] = "l.state_id = " . intval($state_id);
    }
    if ($county_name !== '') {
        $where[] = "l.county_name LIKE '%" . $conn->real_escape_string($county_name) . "%'";
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
        $where[] = "cb.Affordability_Tier = '" . $conn->real_escape_string($affordability) . "'";

    $where_sql = implode(" AND ", $where);

    $sql = "
        SELECT l.county_id, l.County_Name, ss.State_Name,
            i.Median_Household_Income, e.Unemployment_Rate_Pct,
            ed.Pct_Any_Degree, c.Median_Gross_Rent,
            cb.Affordability_Tier, a2.Median_Age,
            an.Composite_Opportunity_Score
        FROM Location l
        JOIN State_Summary ss ON l.state_id = ss.state_id
        JOIN Income i ON l.county_id = i.county_id
        JOIN Employment e ON l.county_id = e.county_id
        JOIN Education_Distribution ed ON l.county_id = ed.county_id
        JOIN Cost_of_Living c ON l.county_id = c.county_id
        JOIN Cost_Burden_Classification cb ON l.county_id = cb.county_id
        JOIN Gender g ON l.county_id = g.county_id
        JOIN Race r ON l.county_id = r.county_id
        JOIN Occupation o ON l.county_id = o.county_id
        JOIN Age_Distribution a2 ON l.county_id = a2.county_id
        JOIN Analysis an ON l.county_id = an.county_id
        WHERE $where_sql
        ORDER BY an.Composite_Opportunity_Score DESC
        LIMIT 200
        ";
    
    $query = $conn->query($sql);
    while ($row = $query->fetch_assoc()) {
        $results[] = $row;
    }
    $result_count = count($results);
}

$states_result = $conn->query("SELECT state_id, State_Name FROM State_Summary ORDER BY State_Name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Explorer - SocioEconomic Insights</title>
    <link rel="stylesheet" href="css/style.css">
    <script src = "https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
</head>
<body>

<nav>
  <span class="brand"><span style="width:22px;height:22px;background:#2563eb;color:#fff;font-size:11px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;font-family:var(--mono)">SEI</span><span>SocioEconomic Insights</span></span>
  <a href="index.php" class="active">Dashboard</a>
  <a href="search.php">Search</a>
  <a href="compare.php">Compare</a>
  <a href="rankings.php">Rankings</a>
  <a href="demographics.php">Demographics</a>
  <a href="education.php">Education</a>
  <a href="cost.php">Cost of Living</a>
</nav>

<div class="page">
    <div class="page-header">
        <h1>County Explorer</h1>
        <p>Filter counties across the U.S. by income, education, demographics, occupation, and cost of living.</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div style="background:var(--danger-dim);border:1px solid var(--danger);border-radius:var(--radius);padding:12px 12px;margin-bottom:1.5rem">
            <?php foreach ($errors as $e): ?>
                <div style="font-size:13px;color:var(--danger)"><?php echo htmlspecialchars($e); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card" style="margin-bottom:1.5rem">
        <form method="GET" action="search.php">
            <p class="section-label">Location</p>
            <div class="filter-group">
                <label>State</label>
                <select name="state_id">
                    <option value="">All states</option>
                    <?php while ($s = $states_result->fetch_assoc()): ?>
                        <option value="<?php echo $s['state_id']; ?>" <?php echo $state_id == $s['state_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($s['State_Name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>County Name Contains</label>
                <input type="text" name="county_name" value="<?php echo htmlspecialchars($county_name); ?>" placeholder="e.g. 'Olmsted'">
            </div>
        </div>

        <p class="section-label">Income &amp; Education</p>
        <div class="filter-grid" style="margin-bottom:1rem">
            <div class="filter-group">
                <label>Minimum household income ($)</label>
                <input type="number" name="income_min" value="<?php echo htmlspecialchars($income_min); ?>" placeholder="e.g. 50000" min="0"
                class="<?php echo (in_array('Minimum income must be a positive integer.', $errors) || in_array('Minimum income cannot be greater than maximum income.', $errors)) ? 'invalid' : ''; ?>">
            </div>
            <div class="filter-group">
                <label>Maximum household income ($)</label>
                <input type="number" name="income_max" value="<?php echo htmlspecialchars($income_max); ?>" placeholder="e.g. 100000" min="0"
                class="<?php echo (in_array('Maximum income must be a positive integer.', $errors) || in_array('Maximum income cannot be less than minimum income.', $errors)) ? 'invalid' : ''; ?>">
            </div>
            <div class="filter-group">
                <label>Minimum % with any degree</label>
                <input type="number" name="edu_min" value="<?php echo htmlspecialchars($edu_min); ?>" placeholder="e.g. 40" min="0" max="100"
                class="<?php echo in_array('Education percentage must be a number between 0 and 100.', $errors) ? 'invalid' : ''; ?>">
            </div>
        </div>

        <p class="section-label">Demographics</p>
        <div class="filter-grid" style="margin-bottom:1rem">
            <div class="filter-group">
                <label>Gender Majority</label>
                <select name="gender">
                    <option value="">Any</option>
                    <option value="majority_female" <?php echo $gender === 'majority_female' ? 'selected' : ''; ?>>Majority female</option>
                    <option value="majority_male" <?php echo $gender === 'majority_male' ? 'selected' : ''; ?>>Majority male</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Race / Ethnicity</label>
                <select name="race">
                    <option value="">Any</option>
                    <option value="white" <?php echo $race === 'white' ? 'selected' : ''; ?>>White</option>
                    <option value="black" <?php echo $race === 'black' ? 'selected' : ''; ?>>Black</option>
                    <option value="hispanic" <?php echo $race === 'hispanic' ? 'selected' : ''; ?>>Hispanic</option>
                    <option value="asian" <?php echo $race === 'asian' ? 'selected' : ''; ?>>Asian</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Minimum % of selected race</label>
                <input type="number" name="race_pct" value="<?php echo htmlspecialchars($race_pct); ?>" placeholder="e.g. 20" min="0" max="100"
                class="<?php echo in_array('Race percentage must be a number between 0 and 100.', $errors) ? 'invalid' : ''; ?>">
            </div>
            <div class="filter-group">
                <label>Minimum median age</label>
                <input type="number" name="age_min" value="<?php echo htmlspecialchars($age_min); ?>" placeholder="e.g. 30" min="0"
                class="<?php echo (in_array('Minimum age must be a positive number.', $errors) || in_array('Age cannot be negative.', $errors)) ? 'invalid' : ''; ?>">
            </div>
            <div class="filter-group">
                <label>Maximum median age</label>
                <input type="number" name="age_max" value="<?php echo htmlspecialchars($age_max); ?>" placeholder="e.g. 50" min="0"
                class="<?php echo (in_array('Maximum age must be a positive number.', $errors) || in_array('Age cannot be negative.', $errors)) ? 'invalid' : ''; ?>">
            </div>
        </div>

        <p class="section-label"> Occupation &amp; housing</p>
        <div class="filter-grid" style="margin-bottom:1rem">
            <div class="filter-group">
                <label>Majority occupation</label>
                <select name="occupation">
                    <option value="">Any</option>
                    <option value="management" <?php echo $occupation === 'management' ? 'selected' : ''; ?>>Management</option>
                    <option value="service" <?php echo $occupation === 'service' ? 'selected' : ''; ?>>Service</option>
                    <option value="sales" <?php echo $occupation === 'sales' ? 'selected' : ''; ?>>Sales</option>
                    <option value="naturalres" <?php echo $occupation === 'naturalres' ? 'selected' : ''; ?>>Natural Resources</option>
                    <option value="production" <?php echo $occupation === 'production' ? 'selected' : ''; ?>>Production</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Housing affordability tier</label>
                <select name="affordability">
                    <option value="">Any</option>
                    <option value="Affordable" <?php echo $affordability === 'Affordable' ? 'selected' : ''; ?>>Affordable</option>
                    <option value="Moderate" <?php echo $affordability === 'Moderate' ? 'selected' : ''; ?>>Moderate</option>
                    <option value="Expensive" <?php echo $affordability === 'Expensive' ? 'selected' : ''; ?>>Expensive</option>
                    <option value="Extreme" <?php echo $affordability === 'Extreme' ? 'selected' : ''; ?>>Extreme</option>
                </select>
            </div>
        </div>
        <div class="btn-row">
            <button type="submit" name="search" class="btn btn-primary">Search</button>
            <a href="search.php" class="btn btn-outline">Reset</a>
            <?php if ($searched): ?>
                <span style="font-size:12px;color:var(--text3);font-family:var(--mono);margin-left:auto"><?php echo $result_count; ?> result<?php echo $result_count !== 1 ? 's' : ''; ?></span>
            <?php endif; ?>
        </div>
        </form>
    </div>

    <?php if ($searched && empty($errors)): ?>
        <div class="card" style="padding:0;overflow:hidden">
            <?php if ($result_count > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>County</th>
                        <th>State</th>
                        <th>Median Income</th>
                        <th>$ with Degree</th>
                        <th>Unemployment Rate</th>
                        <th>Median Rent</th>
                        <th>Affordability Tier</th>
                        <th>Median Age</th>
                        <th>Opportunity Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $r): ?>
                        <tr onclick="window.location='county.php?county_id=<?php echo $r['county_id']; ?>'" style="cursor:pointer">
                            <td><strong><?php echo htmlspecialchars($r['County_Name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($r['State_Name']); ?></td>
                            <td style="font-family:var(--mono);font-size:12px"><?php echo number_format($r['Median_Household_Income']); ?></td>
                            <td style="font-family:var(--mono);font-size:12px"><?php echo round($r['Pct_Any_Degree'] * 100, 1); ?>%</td>
                            <td style="font-family:var(--mono);font-size:12px"><?php echo $r['Unemployment_Rate_Pct']; ?>%</td>
                            <td style="font-family:var(--mono);font-size:12px">$<?php echo number_format($r['Median_Gross_Rent']); ?></td>
                            <td><span class="tier-badge tier-<?php echo strtolower($r['Affordability_Tier']); ?>"><?php echo $r['Affordability_Tier']; ?></span></td>
                            <td style="font-family:var(--mono);font-size:12px"><?php echo round($r['Median_Age'], 1); ?></td>
                            <td><span class="score-val"><?php echo round($r['Composite_Opportunity_Score'] * 100, 1); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
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