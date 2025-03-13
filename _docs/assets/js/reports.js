/**
 * assets/js/reports.js
 * Report functionality JavaScript
 */

$(document).ready(function() {
    // Initialize date pickers for report filters
    if($.fn.datepicker) {
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        });
    }
    
    // Filter form submission
    $(".report-filter-form").submit(function(event) {
        // Allow normal form submission - no preventDefault()
        
        // Validate date ranges if both start and end dates are provided
        var startDate = $(this).find("input[name='start_date']").val();
        var endDate = $(this).find("input[name='end_date']").val();
        
        if (startDate && endDate) {
            if (new Date(startDate) > new Date(endDate)) {
                event.preventDefault();
                alert("Start date cannot be later than end date.");
                return false;
            }
        }
    });
    
    // Report export functionality
    $(".export-report").click(function(event) {
        event.preventDefault();
        
        var reportType = $(this).data("type");
        var projectId = $(this).data("project");
        var format = $(this).data("format");
        
        // Build URL with all current filter parameters
        var currentUrl = window.location.href;
        var urlParams = new URLSearchParams(window.location.search);
        
        // Add or update export format parameter
        urlParams.set("export", format);
        
        // Create export URL
        var exportUrl = BASE_URL + "/reports.php?" + urlParams.toString();
        
        // Redirect to export URL
        window.location.href = exportUrl;
    });
    
    // Print report
    $(".print-report").click(function(event) {
        event.preventDefault();
        window.print();
    });
    
    // Update charts when DOM is fully loaded
    updateCharts();
});

/**
 * Update all charts on the page
 */
function updateCharts() {
    // Initialize Chart.js charts if available on the page
    if (typeof Chart !== 'undefined') {
        // Charts are created through inline PHP in the view files
        // This function can be used to resize or update charts if needed
        
        // Listen for tab changes to resize charts
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            if (window.statusChart) window.statusChart.resize();
            if (window.priorityChart) window.priorityChart.resize();
            if (window.deliverableChart) window.deliverableChart.resize();
            if (window.productivityChart) window.productivityChart.resize();
        });
    }
}

/**
 * Generate CSV data from table
 * 
 * @param {HTMLElement} table The table element to convert to CSV
 * @returns {string} CSV data
 */
function tableToCSV(table) {
    var csv = [];
    var rows = table.querySelectorAll('tr');
    
    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (var j = 0; j < cols.length; j++) {
            // Replace any commas and quotes in the cell text
            var text = cols[j].innerText.replace(/"/g, '""');
            row.push('"' + text + '"');
        }
        
        csv.push(row.join(','));
    }
    
    return csv.join('\n');
}

/**
 * Download CSV data as a file
 * 
 * @param {string} csv The CSV data
 * @param {string} filename The filename for the download
 */
function downloadCSV(csv, filename) {
    var csvFile;
    var downloadLink;
    
    // Create CSV file and download link
    csvFile = new Blob([csv], {type: "text/csv"});
    downloadLink = document.createElement("a");
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    
    // Add to DOM and trigger download
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}