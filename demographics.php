<?php
include "db_connection.php";

$state_id = isset($_GET['state_id']) ? intval($_GET['state_id']) : 0; //Get state ID from query parameter, default to 0 (all states) if not set
$view = isset($_GET['view']) ? $_GET['view'] : 'race'; //Get view from query parameter
$searched = isset($_GET['load']); //Check if the page is being loaded with previous search

$results = []; //Initialize results array to store query results for display

if ($searched) { //If the page is being loaded with a search, run correct query based on view and state
    $where = $state_id > 0 ? "WHERE l.state_id = $state_id" : ""; 
    $sql = "
        SELECT l.County_Name, ss.State_Name, l.county_id,
            g.Total_Pop, g.Pct_Male, g.Pct_Female,
            r.Pct_White, r.Pct_Black, r.Pct_Asian, r.Pct_Hispanic,
            a2.Median_Age, i.Median_Household_Income
        FROM Location l
        JOIN State_Summary ss ON l.state_id = ss.state_id
        JOIN Gender g ON l.county_id = g.county_id
        JOIN Race r ON l.county_id = r.county_id
        JOIN Age_Distribution a2 ON l.county_id = a2.county_id
        JOIN Income i ON l.county_id = i.county_id
        $where
        ORDER BY g.Total_Pop DESC
        LIMIT 200
    ";
    $q = $conn->query($sql); //Run  query and store results in $results array
    while ($row = $q->fetch_assoc()) $results[] = $row; //Fetch each row of the result set as an associative array and append it to the $results array for later
}

$states_result = $conn->query("SELECT state_id, State_Name FROM State_Summary ORDER BY State_Name"); //Get list of states for dropdown menu
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Demographics</title>
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
        <h1>Demographics</h1>
        <p>Explore demographic data across US counties</p>
    </div>
</div>

<div class="card" style="margin-bottom:1.5rem">
    <form method="GET" action="demographics.php">
        <div class="filter-group">
            <label>Filter by state</label>
            <select name="state_id">
                <option value="">All states</option>
                <?php while ($state_row = $states_result->fetch_assoc()): ?>
                    <option value="<?php echo $state_row['state_id']; ?>" 
                        <?php echo $state_id == $state_row['state_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($state_row['State_Name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="filter-group">
            <label>View</label>
            <select name="view">
                <option value="race" <?php echo $view === 'race' ? 'selected' : ''; ?>>Race &amp; ethnicity</option>
                <option value="gender" <?php echo $view === 'gender' ? 'selected' : ''; ?>>Gender</option>
                <option value="age" <?php echo $view === 'age' ? 'selected' : ''; ?>>Age &amp; income</option>
            </select>
        </div>
        <div class ="btn-row">
            <button type="submit" name="load" class="btn btn-primary">Load data</button>
        </div>
    </form>
</div>

<?php if ($searched && count($results) > 0):
    $count = count($results);
    $avg = function($key) use ($results, $count) {
        return round(array_sum(array_column($results, $key)) / $count * 100, 1);
    };
?>

<?php if ($view === 'race'): ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">
        <div class="card">
            <p class="section-label">Average racial composition across <?php echo $count; ?> counties</p>
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
                <span style="font-family:var(--mono);color:var(--text3);width:18px"><?php echo $i+1; ?></span>
                <span style="flex:1"><?php echo htmlspecialchars($r['County_Name']); ?></span>
                <span style="color:var(--text3)"><?php echo htmlspecialchars($r['State_Name']); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php elseif ($view === 'gender'): ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">
            <p class="section-label">Gender summary</p>
            <div class="card">
                <div class="stat-grid">
                    <div class="stat-card"><div class="slabel">Average % Female</div><div class="sval"><?php echo $avg('Pct_Female'); ?>%</div></div>
                    <div class="stat-card"><div class="slabel">Average % Male</div><div class="sval"><?php echo $avg('Pct_Male'); ?>%</div></div>
                </div>
            </div>
        </div>

    <?php elseif ($view === 'age'): ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">
            <div class="card">
                <p class="section-label">Median age distribution across <?php echo $count; ?> counties</p>
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
                    <div class="stat-card"><div class="slabel">Average median age</div><div class="sval"><?php echo $avg_age; ?></div></div>
                    <div class="stat-card"><div class="slabel">Range</div><div class="sval" style="font-size:12px"><?php echo $min_age; ?> - <?php echo $max_age; ?></div></div>
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
                            <th>% White</th><th>% Black</th><th>% Asian</th><th>% Hispanic</th>
                        <?php elseif ($view === 'gender'): ?>
                            <th>Population</th><th>% Female</th><th>% Male</th>
                        <?php else: ?>
                            <th>Median Age</th><th>Median Household Income</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $r): ?>
                    <tr onclick="window.location='county.php?county_id=<?php echo $r['county_id']; ?>'" style="cursor:pointer">
                        <td><?php echo htmlspecialchars($r['County_Name']); ?></td>
                        <td style="color:var(--text3)"><?php echo htmlspecialchars($r['State_Name']); ?></td>
                        <?php if ($view === 'race'): ?>
                        <td style="font-family:var(--mono);font-size:12px"><?php echo round($r['Pct_White'] * 100, 1); ?>%</td>
                        <td style="font-family:var(--mono);font-size:12px"><?php echo round($r['Pct_Black'] * 100, 1); ?>%</td>
                        <td style="font-family:var(--mono);font-size:12px"><?php echo round($r['Pct_Asian'] * 100, 1); ?>%</td>
                        <td style="font-family:var(--mono);font-size:12px"><?php echo round($r['Pct_Hispanic'] * 100, 1); ?>%</td>
                        <?php elseif ($view === 'gender'): ?>
                        <td style="font-family:var(--mono);font-size:12px"><?php echo number_format($r['Total_Pop']); ?></td>
                        <td style="font-family:var(--mono);font-size:12px"><?php echo round($r['Pct_Female'] * 100, 1); ?>%</td>
                        <td style="font-family:var(--mono);font-size:12px"><?php echo round($r['Pct_Male'] * 100, 1); ?>%</td>
                        <?php else: ?>
                        <td style="font-family:var(--mono);font-size:12px"><?php echo round($r['Median_Age'], 1); ?></td>
                        <td style="font-family:var(--mono);font-size:12px"><?php echo number_format($r['Median_Household_Income']); ?></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
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
                        data: [<?php echo $avg('Pct_White'); ?>, <?php echo $avg('Pct_Black'); ?>, <?php echo $avg('Pct_Asian'); ?>, <?php echo $avg('Pct_Hispanic'); ?>],
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
                            data: [<?php echo $avg('Pct_Female'); ?>, <?php echo $avg('Pct_Male'); ?>],
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
                const ages = <?php echo json_encode(array_values(array_column($results, 'Median_Age'))); ?>;
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
</body>
</html>