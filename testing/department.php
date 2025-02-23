<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Count per Department</title>
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
  </style>
</head>
<body>
  <div class="container">
    <!-- Employee Count per Department Card -->
    <div class="col-xl-3">
      <div class="card mb-4">
        <div class="card-header">
          <i class="fas fa-users me-1"></i>
          <a class="text-light" href="#">Employee Count per Department</a>
        </div>
        <div class="card-body">
          <canvas id="employeeDepartmentChart" width="300" height="300"></canvas>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Dummy Data for Employee Count per Department
    const departments = [
      { name: "Sales", count: 25 },
      { name: "Marketing", count: 15 },
      { name: "Finance", count: 10 },
      { name: "HR", count: 8 },
      { name: "IT", count: 20 },
      { name: "Credit", count: 12 }, <!-- Added Credit Department -->
    ];

    // Employee Count per Department Bar Chart
    const employeeDepartmentCtx = document.getElementById("employeeDepartmentChart").getContext("2d");
    new Chart(employeeDepartmentCtx, {
      type: "bar",
      data: {
        labels: departments.map(dept => dept.name),
        datasets: [{
          label: "Employee Count",
          data: departments.map(dept => dept.count),
          backgroundColor: "#1abc9c", // Bar color
          borderColor: "#34495e", // Border color
          borderWidth: 1,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false, // Hide legend for bar chart
          },
          tooltip: {
            enabled: true,
            callbacks: {
              label: function (context) {
                const label = context.label || "";
                const value = context.raw || 0;
                return `${label}: ${value} employees`;
              },
            },
          },
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