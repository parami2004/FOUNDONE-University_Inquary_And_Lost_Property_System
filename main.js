document.addEventListener("DOMContentLoaded", function () {
    const reportForm = document.getElementById("reportForm");
    if (reportForm) {
        reportForm.addEventListener("submit", function (event) {
            event.preventDefault();
            const itemName = document.getElementById("itemName").value.trim();
            alert(` Success! Your report for "${itemName}" has been submitted.`);
            reportForm.reset();
        });
    }
});