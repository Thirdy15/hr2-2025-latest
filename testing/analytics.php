<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Performance Analytics</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <style>
    /* General Styles */
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      background-color: #1a1a1a; /* Dark background */
      color: #fff;
      height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .container {
      flex: 1;
      padding: 20px;
      max-width: 1200px;
      margin: 0 auto;
      overflow-y: auto;
    }

    .card {
      background: #2c3e50; /* Dark card background */
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      margin-bottom: 20px;
    }

    .card-header {
      background: #34495e; /* Slightly lighter header */
      padding: 15px;
      border-bottom: 1px solid #4a5f78;
      font-size: 18px;
      font-weight: bold;
      color: #fff;
    }

    .card-header a {
      color: #fff;
      text-decoration: none;
    }

    .card-header a:hover {
      color: #1abc9c; /* Highlight color */
    }

    .card-body {
      padding: 20px;
    }

    canvas {
      background: #34495e; /* Dark chart background */
      border-radius: 10px;
      padding: 10px;
    }

    /* Table Styles */
    .table-container {
      background: #2c3e50;
      border-radius: 10px;
      padding: 20px;
      overflow-x: auto;
      max-height: 300px;
      overflow-y: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 600px;
    }

    table th, table td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #4a5f78;
      color: #fff;
    }

    table th {
      background-color: #34495e;
    }

    table tbody tr:hover {
      background-color: #4a5f78;
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Employee Performance Card -->
    <div class="col-xl-3">
      <div class="card mb-4">
        <div class="card-header">
          <i class="fas fa-chart-line me-1"></i>
          <a class="text-light" href="#">Employee Performance</a>
        </div>
        <div class="card-body">
          <canvas id="employeePerformanceChart" width="300" height="300"></canvas>
        </div>
      </div>
    </div>

    <!-- Employee Performance Table -->
    <div class="table-container">
      <table id="employee-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Loans Disbursed</th>
            <th>Recovery Rate</th>
            <th>Customer Rating</th>
          </tr>
        </thead>
        <tbody>
          <!-- Rows will be populated by JavaScript -->
        </tbody>
      </table>
    </div>
  </div>

  <script>
    // Dummy Data
    const employees = [
      { name: "John Doe", loansDisbursed: 50, recoveryRate: 95, customerRating: 4.5 },
      { name: "Jane Smith", loansDisbursed: 45, recoveryRate: 85, customerRating: 4.2 },
      { name: "Alice Johnson", loansDisbursed: 60, recoveryRate: 90, customerRating: 4.7 },
    ];

    // Populate Table
    const tableBody = document.querySelector("#employee-table tbody");
    employees.forEach(employee => {
      const row = document.createElement("tr");
      row.innerHTML = `
        <td>${employee.name}</td>
        <td>${employee.loansDisbursed}</td>
        <td>${employee.recoveryRate}%</td>
        <td>${employee.customerRating}</td>
      `;
      tableBody.appendChild(row);
    });

    // Employee Performance Chart
    const employeePerformanceCtx = document.getElementById("employeePerformanceChart").getContext("2d");
    new Chart(employeePerformanceCtx, {
      type: "bar",
      data: {
        labels: employees.map(emp => emp.name),
        datasets: [{
          label: "Loans Disbursed",
          data: employees.map(emp => emp.loansDisbursed),
          backgroundColor: "#1abc9c", // Highlight color
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: true },
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              color: "#4a5f78", // Grid color
            },
            ticks: {
              color: "#fff", // Y-axis text color
            },
          },
          x: {
            grid: {
              color: "#4a5f78", // Grid color
            },
            ticks: {
              color: "#fff", // X-axis text color
            },
          },
        },
      },
    });
  </script>
</body>
</html>