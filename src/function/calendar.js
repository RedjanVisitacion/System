const monthYear = document.getElementById("month-year");
const dates = document.getElementById("dates");
const prevBtn = document.getElementById("prev");
const nextBtn = document.getElementById("next");
const todayBtn = document.getElementById("today");

const today = new Date();
let currentMonth = today.getMonth();
let currentYear = today.getFullYear();

const renderCalendar = () => {
  const firstDay = new Date(currentYear, currentMonth, 1);
  const lastDate = new Date(currentYear, currentMonth + 1, 0).getDate();
  const startDay = firstDay.getDay();

  monthYear.textContent = firstDay.toLocaleDateString("default", {
    month: "long",
    year: "numeric"
  });

  dates.innerHTML = "";

  for (let i = 0; i < startDay; i++) {
    dates.innerHTML += `<div></div>`;
  }

  for (let day = 1; day <= lastDate; day++) {
    const isToday =
      day === today.getDate() &&
      currentMonth === today.getMonth() &&
      currentYear === today.getFullYear();

    dates.innerHTML += `<div class="${isToday ? "today" : ""}">${day}</div>`;
  }
};

prevBtn.addEventListener("click", () => {
  currentMonth--;
  if (currentMonth < 0) {
    currentMonth = 11;
    currentYear--;
  }
  renderCalendar();
});

nextBtn.addEventListener("click", () => {
  currentMonth++;
  if (currentMonth > 11) {
    currentMonth = 0;
    currentYear++;
  }
  renderCalendar();
});

todayBtn.addEventListener("click", () => {
  currentMonth = today.getMonth();
  currentYear = today.getFullYear();
  renderCalendar();
});

renderCalendar();
