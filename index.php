<?php
session_start();
include "db_connection.php";

$result = $conn->query("SELECT COUNT(*) AS count FROM Location"); //Get total number of counties
$total_counties = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) AS count FROM State_Summary"); //Get total number of states (including D.C. and P.R.)
$total_states = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT ROUND(AVG(Median_Household_Income), 0) AS avg FROM Income WHERE Median_Household_Income > 0"); //Get average median household income across all counties
$avg_income = $result->fetch_assoc()['avg']; 

$result = $conn->query("SELECT ROUND(AVG(Unemployment_Rate_Pct), 2) AS avg FROM Employment WHERE Unemployment_Rate_Pct >= 0"); //Get average unemployment rate across all counties
$avg_unemployment = $result->fetch_assoc()['avg'];

$result = $conn->query("SELECT ROUND(AVG(Median_Gross_Rent), 0) AS avg FROM Cost_of_Living WHERE Median_Gross_Rent > 0"); //Get average median gross rent across all counties
$avg_rent = $result->fetch_assoc()['avg'];

$result = $conn->query("SELECT ROUND(AVG(Pct_Any_Degree) * 100, 1) AS avg FROM Education_Distribution WHERE Pct_Any_Degree IS NOT NULL"); //Get average percentage of adults with any degree across all counties
$avg_edu = $result->fetch_assoc()['avg'];

//Top 10 counties by income
$top_income = $conn->query(" 
  SELECT l.County_Name, ss.State_Name, i.Median_Household_Income
  FROM Location l
  JOIN State_Summary ss ON l.state_id = ss.state_id
  JOIN Income i ON l.county_id = i.county_id
  ORDER BY i.Median_Household_Income DESC
  LIMIT 10
");

//Top 10 by opportunity score
$top_opp = $conn->query("
  SELECT l.County_Name, ss.State_Name, an.Composite_Opportunity_Score
  FROM Location l
  JOIN State_Summary ss ON l.state_id = ss.state_id
  JOIN Analysis an ON l.county_id = an.county_id
  ORDER BY an.Composite_Opportunity_Score DESC
  LIMIT 10
");

//Lowest Unemployment Counties
$low_unemp = $conn->query("
  SELECT l.County_Name, ss.State_Name, e.Unemployment_Rate_Pct
  FROM Location l
  JOIN State_Summary ss ON l.state_id = ss.state_id
  JOIN Employment e ON l.county_id = e.county_id
  ORDER BY e.Unemployment_Rate_Pct ASC
  LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - SocioEconomic Insights</title>
  <link rel="stylesheet" href="css/style.css">
  <script src = "https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
</head>
<body>

<nav>
  <span class="brand">
    <span style="width:22px;height:22px;background:#2563eb;color:#fff;font-size:11px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;font-family:var(--mono)">SEI</span>
    <span>SocioEconomic Insights</span>
  </span>
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
    <h1>National Overview</h1>
    <p>SocioEconomic overview of all U.S. counties - based on 2024 ACS data.</p>
  </div>
<!-- Stat cards with key metrics -->
 <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;margin-bottom:2rem">
  <div class="stat-card"><div class="slabel">Counties</div><div class="sval"><?php echo number_format($total_counties); ?></div></div>
  <div class="stat-card"><div class="slabel">States including D.C. & P.R.</div><div class="sval"><?php echo $total_states; ?></div></div>
  <div class="stat-card"><div class="slabel">Average Median Income</div><div class="sval">$<?php echo number_format($avg_income); ?></div></div>
  <div class="stat-card"><div class="slabel">Average Unemployment</div><div class="sval"><?php echo $avg_unemp; ?>%</div></div>
  <div class="stat-card"><div class="slabel">Average Median Rent</div><div class="sval">$<?php echo number_format($avg_rent); ?></div></div>
  <div class="stat-card"><div class="slabel">Average Degree Rate</div><div class="sval"><?php echo $avg_edu; ?>%</div></div>
 </div>

 <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">
  <div class="card">
    <p class="section-label">Top 10 Counties by Median Income</p>
    <canvas id="chart-income" height="220"></canvas>
  </div>
  <div class="card">
    <p class="section-label">Top 10 Counties by Opportunity Score</p>
    <canvas id="chart-opportunity" height="220"></canvas>
  </div>
  <div class="card">
    <p class="section-label">Lowest Unemployment Counties</p>
    <div style="display:flex;flex-direction:column;gap:8px;margin-top:.25rem">
      <?php $rank = 1; while ($row = $low_unemp->fetch_assoc()): ?>
        <div style="display:flex;align-items:center;gap:10px;font-size:13px">
          <span style="font-family:var(--mono);color:var(--text3);width:20px"><?php echo $rank++; ?></span>
          <span style="flex:1;color:var(--text)"><?php echo htmlspecialchars($row['County_Name']); ?></span>
          <span style="color:var(--text3);font-size:12px"><?php echo htmlspecialchars($row['State_Name']); ?></span>
          <span style="font-family:var(--mono);color:var(--success)"><?php echo $row['Unemployment_Rate_Pct']; ?>%</span>
        </div>
      <?php endwhile; ?>
    </div>
  </div>
</div>

<script>
//Prepare data for charts by encoding PHP query results into JavaScript objects
  const incomeData = <?php //income data for top 10 counties
  $labels = []; $vals = []; //reset arrays before reusing
  $top_income->data_seek(0); //reset pointer to start of result set
  while ($row = $top_income->fetch_assoc()) { //loop through results and build labels and values arrays
    $labels[] = str_replace([' County', ' Parish'], '', $row['County_Name']); //remove "County" or "Parish" from labels for cleaner display
    $vals[] = (int)$row['Median_Household_Income']; //cast income to integer for JavaScript
  }
  echo json_encode(['labels' => $labels, 'values' => $vals]); //encode labels and values as JSON for use in JavaScript
  ?>;

  const oppData = <?php //opportunity score data for top 10 counties
  $labels = []; $vals = []; //reset arrays before reusing
  $top_opp->data_seek(0); //reset pointer to start of result set
  while ($row = $top_opp->fetch_assoc()) { //loop through results and build labels and values arrays
    $labels[] = str_replace([' County', ' Parish'], '', $row['County_Name']); //remove "County" or "Parish" from labels for cleaner display
    $vals[] = round($row['Composite_Opportunity_Score'] * 100, 1); //convert score to percentage and round to 1 decimal place for JavaScript
  }
  echo json_encode(['labels' => $labels, 'values' => $vals]); //encode labels and values as JSON for use in JavaScript
  ?>;

  const chartBase = { //configuration shared by both charts
    plugins: {legend: {display: false } },
    scales: {
      x: {ticks: {color: '#5c6180', font: {size: 11} }, grid: {color: 'rgba(255, 255, 255, .04)' } },
      y: {ticks: {color: '#5c6180', font: {size: 11} }, grid: {color: 'rgba(255, 255, 255, .04)' } }
    }
  };

  new Chart(document.getElementById('chart-income'), { //create bar chart for median income
    type: 'bar',
    data: {
      labels: incomeData.labels,
      datasets: [{
        label: 'Median Income',
        data: incomeData.vals,
        backgroundColor: '#5b8af0cc',
        borderRadius: 4,
      }]
    },
    options: {
      ...chartBase,
      scales: {
        ...chartBase.scales,
        y: {
          ...chartBase.scales.y,
          ticks: {
            ...chartBase.scales.y.ticks,
            callback: v=> '$' + (v/1000).toFixed(0) + 'k'}
          }
        }
      }
    });

  new Chart(document.getElementById('chart-opp'), { //create bar chart for opportunity score
    type: 'bar',
    data: {
      labels: oppData.labels,
      datasets: [{
        label: 'Opportunity Score',
        data: oppData.vals,
        backgroundColor: '#4ecb8dcc',
        borderRadius: 4,
      }]
    },
    options: {
      ...chartBase
    }
  });
</script>

</body>
</html>
