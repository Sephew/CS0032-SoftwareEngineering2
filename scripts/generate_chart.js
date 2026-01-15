// generate_chart.js
const { ChartJSNodeCanvas } = require('chartjs-node-canvas');
const fs = require('fs');

// Inputs from PHP via JSON file or command line
const dataFile = process.argv[2]; // path to JSON file with chart data
const outputFile = process.argv[3]; // path to save chart PNG

if (!dataFile || !outputFile) {
    console.error("Usage: node generate_chart.js <data.json> <output.png>");
    process.exit(1);
}

const rawData = fs.readFileSync(dataFile);
const chartData = JSON.parse(rawData);

const width = 800; // px
const height = 400; // px
const chartJSNodeCanvas = new ChartJSNodeCanvas({ width, height });

// Example: bar chart
(async () => {
    const configuration = {
        type: chartData.type || 'bar',
        data: {
            labels: chartData.labels,
            datasets: chartData.datasets
        },
        options: chartData.options || {}
    };

    const image = await chartJSNodeCanvas.renderToBuffer(configuration);
    fs.writeFileSync(outputFile, image);
    console.log(`Chart saved to ${outputFile}`);
})();
