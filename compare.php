<?php
include "db_connection.php";

$id1 = isset($_GET['id1']) ? intval($_GET['id1']) : 0;
$id2 = isset($_GET['id2']) ? intval($_GET['id2']) : 0;
$error = '';
$counties = [];

if (isset($_GET['compare'])) {
    if (!$id1 || !$id2) $error = 'Please select two counties to compare.';
    elseif ($id1 === $id2) $error = 'Please select two different counties to compare.';
    else {
        $stmt = $conn->prepare("
            SELECT l.county_id, l.County_Name, ss.State_Name,
                i.Median_Household_Income,
                e.Unemployment_Rate_Pct, e.Employment_Rate_Pct,
                ed.Pct_Bachelors, ed.Pct_Masters, ed.Pct_Doctorate, ed.Pct_Any_Degree,
                ee.Less_HS_Earn, ee.Bachelors_Earn, ee.Grad_Earn,
                c.Median_Gross_Rent, c.Median_Home_Value,
                cb.Affordability_Tier,
                g.Total_Pop, g.Pct_Male, g.Pct_Female,
                r.Pct_White, r.Pct_Black, r.Pct_Asian, r.Pct_Hispanic,
                a2.Median_Age,
                eg.HS_to_Bachelors_Gap,
                an.Composite_Opportunity_Score,
                an.Income_Percentile, an.LowRent_Percentile,
                an.EduAttain_Percentile, an.EarningsPremium_Percentile
            FROM location l
            JOIN State_Summary ss ON l.state_id = ss.state_id
            JOIN Income i ON l.county_id = i.county_id
            JOIN Employment e ON l.county_id = e.county_id
            JOIN Education_Distribution ed ON l.county_id = ed.county_id
            JOIN Education_Earnings ee ON l.county_id = ee.county_id
            JOIN Cost_of_Living c ON l.county_id = c.county_id
            JOIN Cost_Burden_Classification cb ON l.county_id = cb.county_id
            JOIN Gender g ON l.county_id = g.county_id
            JOIN Race r ON l.county_id = r.county_id
            JOIN Age_Distribution a2 ON l.county_id = a2.county_id
            JOIN Earnings_Gap eg ON l.county_id = eg.county_id
            JOIN Analysis an ON l.county_id = an.county_id
            WHERE l.county_id IN (?, ?)
        ");
        $stmt->bind_param("ii", $id1, $id2);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) $counties[$row['county_id']] = $row;
        $stmt->close();
    }
}

//Load all states for dropdown
$states_result = $conn->query("SELECT state_id, State_Name FROM State_Summary ORDER BY State_Name");
$states = [];
while ($s = $states_result->fetch_assoc()) $states[] = $s;

//Load counties for pre-selected states
$state1 = isset($_GET['state1']) ? intval($_GET['state1']) : 0;
$state2 = isset($_GET['state2']) ? intval($_GET['state2']) : 0;

$counties1 = []; 
$counties2 = [];
if ($state1) {
    $r = $conn->query("SELECT county_id, County_Name FROM Location WHERE state_id=$state1 ORDER BY County_Name");
    while ($row = $r->fetch_assoc()) $counties1[] = $row;
}
if ($state2) {
    $r = $conn->query("SELECT county_id, County_Name FROM Location WHERE state_id=$state2 ORDER BY County_Name");
    while ($row = $r->fetch_assoc()) $counties2[] = $row;
}

function cell($va, $vb, $higher_better = true) {
    if($higher_better) {
        $ca = $va > $vb ? 'color:var(--success)' : ($va < $vb ? 'color:var(--danger)' : '');
        $cb = $vb > $va ? 'color:var(--success)' : ($vb < $va ? 'color:var(--danger)' : '');
    } else {
        $ca = $va < $vb ? 'color:var(--success)' : ($va > $vb ? 'color:var(--danger)' : '');
        $cb = $vb < $va ? 'color:var(--success)' : ($vb > $va ? 'color:var(--danger)' : '');
    }
    return [$ca, $cb];
}
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
        <h1>County Comparison</h1>
        <p>Select two counties to compare across all socioeconomic metrics.</p>
    </div>

    <?php if ($error): ?>
        <div style="background:var(--danger-dim);border:1px solid var(--danger);border-radius:var(--radius);padding:12px 16px;margin-bottom:1.5rem;font-size:13px;color:var(--danger)">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="card" style="margin-bottom:1.5rem">
        <form method="GET" action="compare.php">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
                <div>
                    <p class="section-label">County A</p>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                        <div class="filter-group">
                            <label>State</label>
                            <select name="state1" onchange="this.form.submit()">
                                <option value="">Select state</option>
                                <?php foreach ($states as $s): ?>
                                <option value="<?php echo $s['state_id']; ?>" <?php echo $state1 == $s['state_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s['State_Name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>County</label>
                            <select name="id1">
                                <option value="">Select county</option>
                                <?php foreach ($counties1 as $c): ?>
                                <option value="<?php echo $c['county_id']; ?>" <?php echo $id1 == $c['county_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['County_Name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div>
                    <p class="section-label">County B</p>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                        <div class="filter-group">
                            <label>State</label>
                            <select name="state2" onchange="this.form.submit()">
                                <option value="">Select state</option>
                                <?php foreach ($states as $s): ?>
                                <option value="<?php echo $s['state_id']; ?>" <?php echo $state2 == $s['state_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($s['State_Name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>County</label>
                            <select name="id2">
                                <option value="">Select county</option>
                                <?php foreach ($counties2 as $c): ?>
                                <option value="<?php echo $c['county_id']; ?>" <?php echo $id2 == $c['county_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['County_Name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="btn-row">
                <button type="submit" name="compare" class="btn btn-primary">Compare</button>
                <a href="compare.php" class="btn btn-outline">Reset</a>
            </div>
        </form>
    </div>

    <?php if (count($counties) === 2):
        $a = $counties[$id1] ?? reset($counties);
        $b = $counties[$id2] ?? end($counties);
    ?>
    <div class="card" style="padding:0;overflow:hidden">
        <div style="display:grid;grid-template-columns:220px 1fr 1fr;border-bottom:1px solid var(--border)">
            <div style="padding:14px 16px;font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:var(--text3)">Metric</div>
            <div style="padding:14px 16px; font-weight:600;border-left:1px solid var(--border)"><?php echo htmlspecialchars($a['County_Name']); ?> <span style="font-size:11px;color:var(--text3);font-weight:400"><?php echo htmlspecialchars($a['State_Name']); ?></span></div>
            <div style="padding:14px 16px; font-weight:600;border-left:1px solid var(--border)"><?php echo htmlspecialchars($b['County_Name']); ?> <span style="font-size:11px;color:var(--text3);font-weight:400"><?php echo htmlspecialchars($b['State_Name']); ?></span></div>
        </div>

        <?php
        $metrics = [
            ['Median household income', '$'.number_format($a['Median_Household_Income']), '$'.number_format($b['Median_Household_Income']), $a['Median_Household_Income'], $b['Median_Household_Income'], true],
            ['Unemployment rate', $a['Unemployment_Rate_Pct'].'%', $b['Unemployment_Rate_Pct'].'%', $a['Unemployment_Rate_Pct'], $b['Unemployment_Rate_Pct'], false],
            ['Percentage with any degree', round($a['Pct_Any_Degree'] * 100,1).'%', round($b['Pct_Any_Degree'] * 100, 1).'%', $a['Pct_Any_Degree'], $b['Pct_Any_Degree'], true],
            ['Median gross rent', '$'.number_format($a['Median_Gross_Rent']). '/mo', '$'.number_format($b['Median_Gross_Rent']). '/mo', $a['Median_Gross_Rent'], $b['Median_Gross_Rent'], false],
            ['Median home value', '$'.number_format($a['Median_Home_Value']), '$'.number_format($b['Median_Home_Value']), $a['Median_Home_Value'], $b['Median_Home_Value'], false],
            ['Affordability tier', $a['Affordability_Tier'], $b['Affordability_Tier'], null, null, null],
            ['Median age', round($a['Median_Age'],1), round($b['Median_Age'],1), null, null, null],
            ['Total population', number_format($a['Total_Pop']), number_format($b['Total_Pop']), null, null, null],
            ["Bachelor earnings vs &lt;HS", '$'.number_format($a['HS_to_Bachelors_Gap']), '$'.number_format($b['HS_to_Bachelors_Gap']), $a['HS_to_Bachelors_Gap'], $b['HS_to_Bachelors_Gap'], true],
            ['Opportunity score', round($a['Composite_Opportunity_Score']*100,1), round($b['Composite_Opportunity_Score']*100,1), $a['Composite_Opportunity_Score'], $b['Composite_Opportunity_Score'], true]
        ];

        foreach ($metrics as $i => $m):
            list ($label, $va, $vb, $na, $nb, $hb) = $m;
            $ca = '';
            $cb = '';
            if ($hb !== null && $na !== null && $nb !== null) {
                list($ca, $cb) = cell($na, $nb, $hb);
            }
            $bg = $i % 2 === 0 ? 'background:var(--bg2)' : '';
        ?>
        <div style="display:grid;grid-template-columns:220px 1fr 1fr;border-bottom:1px solid var(--border);<?php echo $bg; ?>">
            <div style="padding:11px 16px;font-size:12px;color:var(--text3)"><?php echo $label; ?></div>
            <div style="padding:11px 16p;font-family:var(--mono);font-size:12px;font-weight:500;border-left:1px solid var(--border);<?php echo $ca; ?>"><?php echo $va; ?></div>
            <div style="padding:11px 16p;font-family:var(--mono);font-size:12px;font-weight:500;border-left:1px solid var(--border);<?php echo $cb; ?>"><?php echo $vb; ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
</body>
</html>