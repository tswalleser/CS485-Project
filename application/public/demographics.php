<?php
require_once dirname(__FILE__) . "/../bootstrap.php";

$state_id = isset($_GET['state_id']) ? intval($_GET['state_id']) : 0; //Get state ID from query parameter, default to 0 (all states) if not set
$view = isset($_GET['view']) ? $_GET['view'] : 'race'; //Get view from query parameter
$searched = isset($_GET['load']); //Check if the page is being loaded with previous search

$results = []; //Initialize results array to store query results for display

if ($searched) { //If the page is being loaded with a search, run correct query based on view and state
    $results = $database->get_demographics($state_id);
}

$states_result = $database->states_result(); //Get list of states for dropdown menu
$id_to_state = [
    0 => 'AllStates'
];
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

  <title>Demographics - SocioEconomic Insights</title>
  <link rel="stylesheet" href="css/style.css">
  <script src = "https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
</head>
<body>
<?php include dirname(__FILE__) . '/includes/navigation.php' ?>

<div class="page">
    <div class="page-header">
        <h1>Demographics</h1>
        <p>Explore demographic data across US counties</p>
    </div>


<div class="card" style="margin-bottom:1.5rem">
    <form method="GET" action="demographics.php">
        <div class="filter-group">
            <label>Filter by state</label>
            <select name="state_id">
                <option value="">All states</option>
                <?php while ($state_row = $states_result->fetch_assoc()): ?>
                    <option value="<?= $state_row['state_id']; ?>" 
                        <?= $state_id == $state_row['state_id'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($state_row['State_Name']); ?>
                        <?php $id_to_state[intval($state_row['state_id'])] = $state_row['State_Name']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="filter-group">
            <label>View</label>
            <select name="view">
                <option value="race" <?= $view === 'race' ? 'selected' : ''; ?>>Race &amp; ethnicity</option>
                <option value="gender" <?= $view === 'gender' ? 'selected' : ''; ?>>Gender</option>
                <option value="age" <?= $view === 'age' ? 'selected' : ''; ?>>Age &amp; income</option>
            </select>
        </div>
        <div class ="btn-row">
            <button type="submit" name="load" class="btn btn-primary">Load data</button>
            <?php if ($session->is_logged_in() && $searched): ?> 
            <a href="./includes/export.php"><button type="button" class="btn btn-primary">Export</button></a>
            <?php endif; ?>
        </div>
    </form>
</div>


<?php if ($searched && count($results) > 0):
    $count = count($results);
    $avg = function($key) use ($results, $count) {
        return round(array_sum(array_column($results, $key)) / $count * 100, 1);
    };

    $export_title = $id_to_state[$state_id] . "Demographics" . strtoupper($view);
    $header = [];
    $data = [];
    $limit = "A1:";
?>

<?php if ($view === 'race'): ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">
        <div class="card">
            <p class="section-label">Average racial composition across <?= $count; ?> counties</p>
            <canvas id="chart-race" height="300"></canvas>
        </div>
        <div class="card">
            <p class="section-label">Top 10 most diverse counties</p>
            <?php
            usort($results, function($a, $b) {
                $da = 1 - max($a['Pct_White'], $a['Pct_Black'], $a['Pct_Asian'], $a['Pct_Hispanic']);
                $db = 1 - max($b['Pct_White'], $b['Pct_Black'], $b['Pct_Asian'], $b['Pct_Hispanic']);
                return $db <=> $da;
            });
            foreach (array_slice($results, 0, 10) as $i => $r): ?>
            <div style="display:flex;align-items:center;gap:10px;font-size:12px;margin-bottom:6px">
                <span style="font-family:var(--mono);color:var(--text3);width:18px"><?= $i+1; ?></span>
                <span style="flex:1"><?= htmlspecialchars($r['County_Name']); ?></span>
                <span style="color:var(--text3)"><?= htmlspecialchars($r['State_Name']); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php elseif ($view === 'gender'): ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">
            <div class="card">
                <p class="section-label">Gender Distribution Across <?= $count; ?> counties</p>
                <canvas id="chart-gender" height="300"></canvas>
            </div>

            <div class="card">
                <p class="section-label">Gender summary</p>
                <div class="stat-grid">
                    <div class="stat-card"><div class="slabel">Average % Female</div><div class="sval"><?= $avg('Pct_Female'); ?>%</div></div>
                    <div class="stat-card"><div class="slabel">Average % Male</div><div class="sval"><?= $avg('Pct_Male'); ?>%</div></div>
                </div>
            </div>
        </div>

    <?php elseif ($view === 'age'): ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">
            <div class="card">
                <p class="section-label">Median age distribution across <?= $count; ?> counties</p>
                <canvas id="chart-age" height="300"></canvas>
            </div>
            <div class="card">
                <p class="section-label">Age summary</p>
                <?php
                $ages = array_column($results, 'Median_Age');
                $avg_age = round(array_sum($ages) / count($ages), 1);
                $min_age = min($ages); $max_age = max($ages);
                ?>
                <div class="stat-grid">
                    <div class="stat-card"><div class="slabel">Average median age</div><div class="sval"><?= $avg_age; ?></div></div>
                    <div class="stat-card"><div class="slabel">Range</div><div class="sval" style="font-size:12px"><?= $min_age; ?> - <?= $max_age; ?></div></div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card" style="padding:0;overflow:hidden">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>County</th>
                        <th>State</th>
                        <?php if ($view === 'race'): ?>
                            <?php $header[] = ['County', 'State', '% White', '% Black', '% Asian', '% Hispanic']; 
                                  $limit = $limit . 'F';
                            ?>
                            <th>% White</th><th>% Black</th><th>% Asian</th><th>% Hispanic</th>
                        <?php elseif ($view === 'gender'): ?>
                            <?php $header[] = ['County', 'State', 'Population', '% Female', '% Male']; 
                                  $limit = $limit . 'E';
                            ?>
                            <th>Population</th><th>% Female</th><th>% Male</th>
                        <?php else: ?>
                            <?php $header[] = ['County', 'State', 'Median Age', 'Median Household Income']; 
                                  $limit = $limit . 'D';
                            ?>
                            <th>Median Age</th><th>Median Household Income</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $r): ?>
                    <tr onclick="window.location='county.php?county_id=<?= $r['county_id']; ?>'" style="cursor:pointer">
                        <td><?= htmlspecialchars($r['County_Name']); ?></td>
                        <td style="color:var(--text3)"><?= htmlspecialchars($r['State_Name']); ?></td>

                        <?php if ($view === 'race'): ?>
                        <?php $data[] = [$r['County_Name'], $r['State_Name'], round($r['Pct_White'] * 100, 1), round($r['Pct_Black'] * 100, 1), round($r['Pct_Asian'] * 100, 1), round($r['Pct_Hispanic'] * 100, 1)]; ?>

                        <td style="font-family:var(--mono);font-size:12px"><?= round($r['Pct_White'] * 100, 1); ?>%</td>
                        <td style="font-family:var(--mono);font-size:12px"><?= round($r['Pct_Black'] * 100, 1); ?>%</td>
                        <td style="font-family:var(--mono);font-size:12px"><?= round($r['Pct_Asian'] * 100, 1); ?>%</td>
                        <td style="font-family:var(--mono);font-size:12px"><?= round($r['Pct_Hispanic'] * 100, 1); ?>%</td>

                        <?php elseif ($view === 'gender'): ?>
                        <?php $data[] = [$r['County_Name'], $r['State_Name'], number_format($r['Total_Pop']), round($r['Pct_Female'] * 100, 1), round($r['Pct_Male'] * 100, 1)]; ?>

                        <td style="font-family:var(--mono);font-size:12px"><?= number_format($r['Total_Pop']); ?></td>
                        <td style="font-family:var(--mono);font-size:12px"><?= round($r['Pct_Female'] * 100, 1); ?>%</td>
                        <td style="font-family:var(--mono);font-size:12px"><?= round($r['Pct_Male'] * 100, 1); ?>%</td>

                        <?php else: ?>
                        <?php $data[] = [$r['County_Name'], $r['State_Name'], round($r['Median_Age'], 1), number_format($r['Median_Household_Income'])]; ?>

                        <td style="font-family:var(--mono);font-size:12px"><?= round($r['Median_Age'], 1); ?></td>
                        <td style="font-family:var(--mono);font-size:12px"><?= number_format($r['Median_Household_Income']); ?></td>

                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>

                    <?php $report->set_report($export_title, $header, $data, $limit . count($data) + 1); ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        <?php if ($view === 'race'): ?>
            new Chart(document.getElementById('chart-race'), {
                type: 'doughnut',
                data: {
                    labels: ['White', 'Black', 'Asian', 'Hispanic'],
                    datasets: [{
                        label: 'Average racial composition',
                        data: [<?= $avg('Pct_White'); ?>, <?= $avg('Pct_Black'); ?>, <?= $avg('Pct_Asian'); ?>, <?= $avg('Pct_Hispanic'); ?>],
                        backgroundColor: ['#2563eb', '#dc2626', '#16a34a', '#eab308']
                    }]
                },
                options: {
                    scales: {
                        y: { beginAtZero: true, max: 1 }
                    }
                }
            });
            <?php elseif ($view === 'gender'): ?>
                new Chart(document.getElementById('chart-gender'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Female', 'Male'],
                        datasets: [{
                            label: 'Average gender distribution',
                            data: [<?= $avg('Pct_Female'); ?>, <?= $avg('Pct_Male'); ?>],
                            backgroundColor: ['#ec4899', '#3b82f6']
                        }]
                    },
                    options: {
                        scales: {
                            y: { beginAtZero: true, max: 1 }
                        }
                    }
                });
            <?php elseif ($view === 'age'): ?>
                const ages = <?= json_encode(array_values(array_column($results, 'Median_Age'))); ?>;
                const buckets = [20, 25, 30, 35, 40, 45, 50, 55, 60, 65, 70];
                const counts = buckets.map((b, i) => ages.filter(v => v >= b && v < (buckets[i+1] || Infinity)).length);
                new Chart(document.getElementById('chart-age'), {
                    type: 'bar',
                    data: {
                        labels: buckets.map((b, i) => b+'-'+(buckets[i+1]||'+')), datasets: [{ data: counts, backgroundColor: '#2563eb', borderRadius: 4}] }, 
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Number of counties',
                                        color: '#888888',
                                        font: { size: 11 }
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Median age range',
                                        color: '#888888',
                                        font: { size: 11 }
                                    }
                                }
                            }
                        }
                    }
                );
            <?php endif; ?>
    </script>
    <?php elseif ($searched): ?>
    <div class="card"><div class="empty-state"><span class="icon">&#128101;</span><p>No data found for selected filters</p></div></div>
    <?php else: ?>
    <div class="card"><div class="empty-state"><span class="icon">&#128101;</span><p>Use the filters above and click "Load data" to explore demographics</p></div></div>
    <?php endif; ?>
</div>
</div>
</body>
</html>