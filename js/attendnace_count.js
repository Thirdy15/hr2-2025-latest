 // Dummy Data for Employee Count per Department
 const departments = [
  { name: "Sales", count: 25 },
  { name: "Marketing", count: 15 },
  { name: "Finance", count: 10 },
  { name: "HR", count: 8 },
  { name: "IT", count: 20 },
  { name: "Credit", count: 12 },
];

// Dummy Data for Employees
const employees = {
  Sales: [
    { id: 1, name: "John Doe" },
    { id: 2, name: "Jane Smith" },
  ],
  Marketing: [
    { id: 3, name: "Alice Johnson" },
    { id: 4, name: "Bob Brown" },
  ],
  Finance: [
    { id: 5, name: "Charlie Davis" },
    { id: 6, name: "David Wilson" },
  ],
  HR: [
    { id: 7, name: "Eva Green" },
    { id: 8, name: "Frank White" },
  ],
  IT: [
    { id: 9, name: "Grace Lee" },
    { id: 10, name: "Henry Brown" },
  ],
  Credit: [
    { id: 11, name: "Ivy Taylor" },
    { id: 12, name: "Jack Black" },
  ],
};

// Function to get today's date in YYYY-MM-DD format
function getTodayDate() {
  const today = new Date();
  return today.toISOString().split("T")[0];
}

// Function to initialize daily attendance data
function initializeDailyAttendance() {
  const today = getTodayDate();
  let attendanceData = JSON.parse(localStorage.getItem("attendanceData")) || {};

  // Check if today's data exists
  if (!attendanceData[today]) {
    // Reset attendance data for today
    attendanceData[today] = {};
    for (const department in employees) {
      attendanceData[today][department] = employees[department].map(emp => ({
        id: emp.id,
        name: emp.name,
        present: false, // Default to absent
      }));
    }
    localStorage.setItem("attendanceData", JSON.stringify(attendanceData));
  }

  return attendanceData[today];
}

// Function to update attendance for an employee
function updateAttendance(department, employeeId, isPresent) {
  const today = getTodayDate();
  let attendanceData = JSON.parse(localStorage.getItem("attendanceData")) || {};

  if (attendanceData[today] && attendanceData[today][department]) {
    const employee = attendanceData[today][department].find(emp => emp.id === employeeId);
    if (employee) {
      employee.present = isPresent;
      localStorage.setItem("attendanceData", JSON.stringify(attendanceData));
    }
  }
}

// Function to show attendance chart for a specific department
function showAttendanceChart(department) {
  const attendanceCard = document.getElementById("attendanceCard");
  const departmentCard = document.getElementById("departmentCard");
  const backButton = document.getElementById("backButton");

  // Hide department card and show attendance card
  departmentCard.style.display = "none";
  attendanceCard.style.display = "block";
  backButton.style.display = "block";

  // Get today's attendance data
  const attendanceData = initializeDailyAttendance();
  const departmentAttendance = attendanceData[department];

  // Destroy existing chart if it exists
  if (attendanceChart) {
    attendanceChart.destroy();
  }

  // Create new chart
  const employeeAttendanceCtx = document.getElementById("employeeAttendanceChart").getContext("2d");
  attendanceChart = new Chart(employeeAttendanceCtx, {
    type: "bar",
    data: {
      labels: departmentAttendance.map(emp => emp.name),
      datasets: [
        {
          label: "Present",
          data: departmentAttendance.map(emp => emp.present ? 1 : 0),
          backgroundColor: "#1abc9c", // Green for present
          borderColor: "#34495e",
          borderWidth: 1,
        },
        {
          label: "Absent",
          data: departmentAttendance.map(emp => emp.present ? 0 : 1),
          backgroundColor: "#e74c3c", // Red for absent
          borderColor: "#34495e",
          borderWidth: 1,
        },
      ],
    },
    options: {
      responsive: false, // Disable responsiveness
      maintainAspectRatio: false, // Disable aspect ratio
      animation: false, // Disable animations
      plugins: {
        legend: {
          display: true,
          position: "top",
          labels: {
            color: "#fff", // Legend text color
          },
        },
        tooltip: {
          enabled: true,
          callbacks: {
            label: function (context) {
              const label = context.dataset.label || "";
              const value = context.raw || 0;
              return `${label}: ${value}`;
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
            maxRotation: 45, // Rotate labels to prevent overlap
            minRotation: 45, // Rotate labels to prevent overlap
            autoSkip: false, // Ensure all labels are shown
          },
        },
      },
    },
  });
}

// Back button functionality
document.getElementById("backButton").addEventListener("click", () => {
  const attendanceCard = document.getElementById("attendanceCard");
  const departmentCard = document.getElementById("departmentCard");
  const backButton = document.getElementById("backButton");

  // Hide attendance card and show department card
  attendanceCard.style.display = "none";
  departmentCard.style.display = "block";
  backButton.style.display = "none";

  // Destroy attendance chart
  if (attendanceChart) {
    attendanceChart.destroy();
    attendanceChart = null;
  }
});

// Employee Count per Department Bar Chart
const employeeDepartmentCtx = document.getElementById("employeeDepartmentChart").getContext("2d");
const departmentChart = new Chart(employeeDepartmentCtx, {
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
    responsive: false, // Disable responsiveness
    maintainAspectRatio: false, // Disable aspect ratio
    animation: false, // Disable animations
    onClick: (event, elements) => {
      if (elements.length > 0) {
        const index = elements[0].index;
        const department = departments[index].name;
        showAttendanceChart(department);
      }
    },
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
          maxRotation: 45, // Rotate labels to prevent overlap
          minRotation: 45, // Rotate labels to prevent overlap
          autoSkip: false, // Ensure all labels are shown
        },
      },
    },
  },
});