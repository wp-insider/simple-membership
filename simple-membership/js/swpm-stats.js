function swpmRenderBarChart({mountPoint, stats, options}) {
    const data = new google.visualization.arrayToDataTable(stats);

    const chart = new google.visualization.ColumnChart(document.getElementById(mountPoint));
    chart.draw(data, options);
}

function swpmRenderPieChart({mountPoint, stats, options}) {
    const data = new google.visualization.arrayToDataTable(stats);

    // Instantiate and draw our chart, passing in some options.
    const chart = new google.visualization.PieChart(document.getElementById(mountPoint));
    chart.draw(data, options);
}