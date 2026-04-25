<?php
require_once dirname(__FILE__) . "/../bootstrap.php";

$id1 = intval($_GET['id1'] ?? -1); //isset($_GET['id1']) ? intval($_GET['id1']) : 0;
$id2 = intval($_GET['id2'] ?? -1); //isset($_GET['id2']) ? intval($_GET['id2']) : 0;

$comparing = $id1 != -1 && $id2 != -1;

$error = '';

$counties = [];

if ($comparing) {
    if ($id1 <= 0 || $id2 <= 0) $error = 'Please select two counties to compare.';
    elseif ($id1 === $id2) $error = 'Please select two different counties to compare.';
    else {
        $counties = $database->compare($id1, $id2);

        if (count($counties) !== 2) {
            $error = 'Something went wrong with the comparison!';
        }
    }
}

//Load all states for dropdown
$states = $database->get_states();

//Load counties for pre-selected states
$state1 = isset($_GET['state1']) ? intval($_GET['state1']) : 0;
$state2 = isset($_GET['state2']) ? intval($_GET['state2']) : 0;

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

  <link rel="apple-touch-icon" sizes="180x180" href="/favicon_io/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/favicon_io/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/favicon_io/favicon-16x16.png">
  <link rel="manifest" href="/favicon_io/site.webmanifest">
  
  <title>Compare - SocioEconomic Insights</title>
  <link rel="stylesheet" href="css/style.css">
  <script src = "https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
  <script src = "js/compare.js"></script>
  <script arc = "js/export.js"></script>
</head>
<body>
<?php include dirname(__FILE__) . '/includes/navigation.php' ?>

<div class="page" style="width: 50%">
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
                <!-- Section A -->

                <div>
                    <p class="section-label">County A</p>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                        <div class="filter-group">
                            <label>State</label>
                            <select id="state1" name="state1" onchange="load_counties('state1', 'county1-select')">
                                <option value="">Select state</option>

                                <?php foreach ($states as $s): ?>
                                <option value="<?= $s['state_id']; ?>" <?= $state1 == $s['state_id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($s['State_Name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>County</label>
                            <select name="id1">
                                <option value="">Select county</option>

                                <div id="county1-select"></div>
                                <script>
                                    load_counties('state1', 'county1-select').then(function() {
                                        <?php
                                            if ($id1 != 0) {
                                                echo "match_county('county1-select', {$_GET['id1']});";
                                            }
                                        ?>
                                    });
                                </script>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Section B -->

                <div>
                    <p class="section-label">County B</p>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                        <div class="filter-group">
                            <label>State</label>
                            <select id="state2" name="state2" onchange="load_counties('state2', 'county2-select')">
                                <option value="">Select state</option>

                                <?php foreach ($states as $s): ?>
                                <option value="<?= $s['state_id']; ?>" <?= $state2 == $s['state_id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($s['State_Name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>County</label>
                            <select name="id2">
                                <option value="">Select county</option>

                                <div id="county2-select"></div>
                                <script> 
                                    load_counties('state2', 'county2-select').then(function() {
                                        <?php
                                            if ($id2 != 0) {
                                                echo "match_county('county2-select', {$_GET['id2']});";
                                            }
                                        ?>
                                    });
                                </script>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="btn-row">
                <button type="submit" name="" class="btn btn-primary">Compare</button>
                <a href="compare.php" class="btn btn-outline">Reset</a>
                <?php if ($session->is_logged_in() && $comparing && strlen($error) == 0): ?> 
                <a href="./includes/export.php"><button type="button" class="btn btn-primary">Export</button></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if ($comparing && strlen($error) == 0):
        $county1 = $counties[$id1];
        $county2 = $counties[$id2];
    ?>
    <div class="card" style="padding:0;overflow:hidden">
        <div style="display:grid;grid-template-columns:220px 1fr 1fr;border-bottom:1px solid var(--border)">
            <div style="padding:14px 16px;font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:var(--text3)">Metric</div>
            <div style="padding:14px 16px; font-weight:600;border-left:1px solid var(--border)"><?= htmlspecialchars($county1['County_Name']); ?> <span style="font-size:11px;color:var(--text3);font-weight:400"><?= htmlspecialchars($county1['State_Name']); ?></span></div>
            <div style="padding:14px 16px; font-weight:600;border-left:1px solid var(--border)"><?= htmlspecialchars($county2['County_Name']); ?> <span style="font-size:11px;color:var(--text3);font-weight:400"><?= htmlspecialchars($county2['State_Name']); ?></span></div>
        </div>

        <?php

        $metrics = [
            ['Median household income', '$'.number_format($county1['Median_Household_Income']), '$'.number_format($county2['Median_Household_Income']), $county1['Median_Household_Income'], $county2['Median_Household_Income'], true],
            ['Unemployment rate', $county1['Unemployment_Rate_Pct'].'%', $county2['Unemployment_Rate_Pct'].'%', $county1['Unemployment_Rate_Pct'], $county2['Unemployment_Rate_Pct'], false],
            ['Percentage with any degree', round($county1['Pct_Any_Degree'] * 100,1).'%', round($county2['Pct_Any_Degree'] * 100, 1).'%', $county1['Pct_Any_Degree'], $county2['Pct_Any_Degree'], true],
            ['Median gross rent', '$'.number_format($county1['Median_Gross_Rent']). '/mo', '$'.number_format($county2['Median_Gross_Rent']). '/mo', $county1['Median_Gross_Rent'], $county2['Median_Gross_Rent'], false],
            ['Median home value', '$'.number_format($county1['Median_Home_Value']), '$'.number_format($county2['Median_Home_Value']), $county1['Median_Home_Value'], $county2['Median_Home_Value'], false],
            ['Affordability tier', $county1['Affordability_Tier'], $county2['Affordability_Tier'], null, null, null],
            ['Median age', round($county1['Median_Age'],1), round($county2['Median_Age'],1), null, null, null],
            ['Total population', number_format($county1['Total_Pop']), number_format($county2['Total_Pop']), null, null, null],
            ["Bachelor earnings vs &lt;HS", '$'.number_format($county1['HS_to_Bachelors_Gap']), '$'.number_format($county2['HS_to_Bachelors_Gap']), $county1['HS_to_Bachelors_Gap'], $county2['HS_to_Bachelors_Gap'], true],
            ['Opportunity score', round($county1['Composite_Opportunity_Score']*100,1), round($county2['Composite_Opportunity_Score']*100,1), $county1['Composite_Opportunity_Score'], $county2['Composite_Opportunity_Score'], true]
        ];

        $header = [[' ', $county1['County_Name'] . ', ' . $county1['State_Name'], $county2['County_Name'] . ', ' . $county2['State_Name']]];
        $data = [];

        foreach ($metrics as $i => $m):
            list ($label, $value1, $value2, $real_value1, $real_value2, $higher_better) = $m;
            
            $data[] = [$label, trim($value1), trim($value2)];

            $style_county1 = '';
            $style_county2 = '';
            if ($higher_better !== null && $real_value1 !== null && $real_value2 !== null) {
                list($style_county1, $style_county2) = cell($real_value1, $real_value2, $higher_better);
            }
            $bg = $i % 2 === 0 ? 'background:var(--bg2)' : '';

            $report->set_report('CompareCountyReport', $header, $data, 'A1:C' . count($data) + 1);
        ?>
        <div style="display:grid;grid-template-columns:220px 1fr 1fr;border-bottom:1px solid var(--border);<?= $bg; ?>">
            <div style="padding:11px 16px;font-size:12px;color:var(--text3)"><?= $label; ?></div>
            <div style="padding:11px 16p;font-family:var(--mono);font-size:12px;font-weight:500;border-left:1px solid var(--border);<?= $style_county1; ?>"><?= $value1; ?></div>
            <div style="padding:11px 16p;font-family:var(--mono);font-size:12px;font-weight:500;border-left:1px solid var(--border);<?= $style_county2; ?>"><?= $value2; ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

</body>
</html>