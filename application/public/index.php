<?php
require_once dirname(__FILE__) . "/../bootstrap.php";

$db_data = $database->get_values();
list($total_counties, $total_states, $avg_income, $avg_unemployment, $avg_rent, $avg_edu) = $db_data['basic'];
list($top_income, $top_opp, $low_unemp) = $db_data['lists'];

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
  
  <title>Dashboard - SocioEconomic Insights</title>
  <link rel="stylesheet" href="css/style.css">
  <script src = "https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
</head>
<body>
<?php include dirname(__FILE__) . '/includes/navigation.php' ?>

<div class="page">
  <div class="page-header">
    <h1>National Overview</h1>
    <p>SocioEconomic overview of all U.S. counties - based on 2024 ACS data.</p>
  </div>
<!-- Stat cards with key metrics -->
 <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(16%,1fr));gap:10px;margin-bottom:2rem">
  <div class="stat-card"><div class="slabel">Counties</div><div class="sval"><?php echo number_format($total_counties); ?></div></div>
  <div class="stat-card"><div class="slabel">States including D.C. & P.R.</div><div class="sval"><?= $total_states; ?></div></div>
  <div class="stat-card"><div class="slabel">Average Median Income</div><div class="sval">$<?= number_format($avg_income); ?></div></div>
  <div class="stat-card"><div class="slabel">Average Unemployment</div><div class="sval"><?= $avg_unemployment; ?>%</div></div>
  <div class="stat-card"><div class="slabel">Average Median Rent</div><div class="sval">$<?= number_format($avg_rent); ?></div></div>
  <div class="stat-card"><div class="slabel">Average Degree Rate</div><div class="sval"><?= $avg_edu; ?>%</div></div>
 </div>

 <div style="display:grid;grid-template-columns: 1fr 1fr 0.5fr;gap:1.5rem;margin-bottom:1.5rem">
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
          <span style="font-family:var(--mono);color:var(--text3);width:20px"><?= $rank++; ?></span>
          <span style="flex:1;color:var(--text)"><?= htmlspecialchars($row['County_Name']); ?></span>
          <span style="color:var(--text3);font-size:12px"><?= htmlspecialchars($row['State_Name']); ?></span>
          <span style="font-family:var(--mono);color:var(--success)"><?= $row['Unemployment_Rate_Pct']; ?>%</span>
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

  document.addEventListener("DOMContentLoaded", function (){
    new Chart(document.getElementById('chart-income'), { //create bar chart for median income
      type: 'bar',
      data: {
        labels: incomeData.labels,
        datasets: [{
          label: 'Median Income',
          data: incomeData.values,
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

    new Chart(document.getElementById('chart-opportunity'), { //create bar chart for opportunity score
      type: 'bar',
      data: {
        labels: oppData.labels,
        datasets: [{
          label: 'Opportunity Score',
          data: oppData.values,
          backgroundColor: '#4ecb8dcc',
          borderRadius: 4,
        }]
      },
      options: {
        ...chartBase
      }
    });
  });
</script>

</body>
</html>
