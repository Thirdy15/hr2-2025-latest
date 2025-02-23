<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Attendance Record</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        h1 {
            color: #333;
        }

        .chart-container {
            width: 80%;
            max-width: 800px;
            margin-top: 20px;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .department-select {
            margin-bottom: 20px;
            padding: 10px;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
    </style>
</head>
<body>
    <h1>Department Attendance Record</h1>
    <select class="department-select" id="departmentSelect">
        <option value="">Show All Departments</option>
        <option value="hr">HR Department</option>
        <option value="it">IT Department</option>
        <option value="sales">Sales Department</option>
        <option value="marketing">Marketing Department</option>
    </select>
    <div class="chart-container">
        <canvas id="attendanceChart"></canvas>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('attendanceChart').getContext('2d');
            const departmentSelect = document.getElementById('departmentSelect');

            // Temporary data for departments and their employees' attendance
            const departmentData = {
                hr: {
                    labels: ['John Doe', 'Jane Smith', 'Alice Johnson', 'Bob Brown'],
                    attendance: ['Present', 'Absent', 'Present', 'Present'] // Attendance status
                },
                it: {
                    labels: ['Mike Ross', 'Harvey Specter', 'Rachel Zane', 'Louis Litt'],
                    attendance: ['Present', 'Present', 'Absent', 'Present']
                },
                sales: {
                    labels: ['Tom Cruise', 'Emma Watson', 'Chris Evans', 'Scarlett Johansson'],
                    attendance: ['Absent', 'Present', 'Present', 'Absent']
                },
                marketing: {
                    labels: ['Tony Stark', 'Steve Rogers', 'Natasha Romanoff', 'Bruce Banner'],
                    attendance: ['Present', 'Present', 'Present', 'Absent']
                }
            };

            // Calculate total present and absent employees for each department
            const departmentSummary = {
                labels: ['HR', 'IT', 'Sales', 'Marketing'],
                present: [
                    departmentData.hr.attendance.filter(status => status === 'Present').length,
                    departmentData.it.attendance.filter(status => status === 'Present').length,
                    departmentData.sales.attendance.filter(status => status === 'Present').length,
                    departmentData.marketing.attendance.filter(status => status === 'Present').length
                ],
                absent: [
                    departmentData.hr.attendance.filter(status => status === 'Absent').length,
                    departmentData.it.attendance.filter(status => status === 'Absent').length,
                    departmentData.sales.attendance.filter(status => status === 'Absent').length,
                    departmentData.marketing.attendance.filter(status => status === 'Absent').length
                ]
            };

            let attendanceChart;

            // Function to create or update the chart
            function updateChart(labels, presentData, absentData = null, label = 'Department Attendance') {
                if (attendanceChart) {
                    attendanceChart.destroy(); // Destroy the existing chart
                }

                const datasets = [
                    {
                        label: 'Present',
                        data: presentData,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }
                ];

                if (absentData) {
                    datasets.push({
                        label: 'Absent',
                        data: absentData,
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    });
                }

                attendanceChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: datasets
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Number of Employees'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: label === 'Department Attendance' ? 'Departments' : 'Employees'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                            },
                            tooltip: {
                                enabled: true
                            }
                        }
                    }
                });
            }

            // Initial chart showing present and absent employees for all departments
            updateChart(departmentSummary.labels, departmentSummary.present, departmentSummary.absent, 'Department Attendance');

            // Event listener for department selection
            departmentSelect.addEventListener('change', function () {
                const selectedDepartment = departmentSelect.value;

                if (selectedDepartment && departmentData[selectedDepartment]) {
                    const { labels, attendance } = departmentData[selectedDepartment];
                    const presentData = attendance.map(status => status === 'Present' ? 1 : 0);
                    const absentData = attendance.map(status => status === 'Absent' ? 1 : 0);
                    updateChart(labels, presentData, absentData, 'Employee Attendance');
                } else {
                    // Show present and absent employees for all departments if no specific department is selected
                    updateChart(departmentSummary.labels, departmentSummary.present, departmentSummary.absent, 'Department Attendance');
                }
            });
        });
    </script>
</body>
</html>