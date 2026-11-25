// public/assets/js/admin.js
document.addEventListener("DOMContentLoaded", () => {
    const container = document.getElementById("adminResultatsCharts");
    if (!container || typeof Chart === "undefined") return;

    try {
        const groupes = JSON.parse(container.dataset.resultats || "[]");
        const palette = ["#3b3e42", "#f38218", "#6c757d", "#d96d08", "#212529", "#fd7e14"];

        groupes.forEach((groupe, idx) => {
            const canvas = document.getElementById("adminChart" + idx);
            if (!canvas) return;

            new Chart(canvas.getContext("2d"), {
                type: "bar",
                data: {
                    labels: groupe.labels || [],
                    datasets: [{
                        label: "Voix",
                        data: groupe.voix || [],
                        backgroundColor: (groupe.labels || []).map((_, i) => palette[i % palette.length]),
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                }
            });
        });
    } catch (err) {
        console.error("Erreur Chart.js Admin :", err);
    }
});