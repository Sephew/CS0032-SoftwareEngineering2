<?php
// exports/export_pdf.php

function exportClusterPdf(array $results, array $cluster_metadata, string $barChartFile, string $pieChartFile, string $radarChartFile, string $comparisonChartFile, string $insights) {
    $pdf = new TCPDF('L'); // Landscape
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 10);

    // Title
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Customer Segmentation Analysis - Cluster Report', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1, 'L');
    $pdf->Ln(5);

    // 1. CLUSTER SUMMARY TABLE
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Cluster Summary Statistics', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 8);

    $html = '<table border="1" cellpadding="2" style="border-collapse: collapse; table-layout: fixed; width: 100%"><tr>';
    $html .= '<th style="width:12%;">Cluster</th>';
    $html .= '<th style="width:10%;">Customers</th>';
    $html .= '<th style="width:10%;">Avg Age</th>';
    $html .= '<th style="width:10%;">Age Range</th>';
    $html .= '<th style="width:10%;">Avg Income</th>';
    $html .= '<th style="width:10%;">Avg Purchase</th>';
    $html .= '<th style="width:12%;">Gender</th>';
    $html .= '<th style="width:16%;">Region</th>';
    $html .= '</tr>';

    foreach($cluster_metadata as $cluster){
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($cluster['cluster_name']) . '</td>';
        $html .= '<td>' . number_format($cluster['customer_count']) . '</td>';
        $html .= '<td>' . round($cluster['avg_age'], 1) . '</td>';
        $html .= '<td>' . $cluster['age_min'] . '-' . $cluster['age_max'] . '</td>';
        $html .= '<td>$' . number_format($cluster['avg_income'], 2) . '</td>';
        $html .= '<td>$' . number_format($cluster['avg_purchase_amount'], 2) . '</td>';
        $html .= '<td>' . htmlspecialchars($cluster['dominant_gender']) . '</td>';
        $html .= '<td>' . htmlspecialchars($cluster['dominant_region']) . '</td>';
        $html .= '</tr>';
    }

    $html .= '</table>';
    $pdf->writeHTML($html, true, false, false, false, '');

    // 2. ANALYSIS INSIGHTS
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Analysis Insights', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5, $insights, 0, 'L');
    $pdf->Ln(5);

    // 3. VISUALIZATIONS (Bar and Pie Charts)
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Visualizations', 0, 1, 'L');
    $chartStartY = $pdf->GetY();

    // Bar chart on the left
    if(file_exists($barChartFile)){
        $pdf->Image($barChartFile, 15, $chartStartY, 120, 65);
    }

    // Pie chart on the right
    if(file_exists($pieChartFile)){
        $pdf->Image($pieChartFile, 145, $chartStartY, 120, 65);
    }

    $pdf->SetY($chartStartY + 70);
    $pdf->Ln(5);

    // 4. CLUSTER CHARACTERISTICS
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Cluster Characteristics & Descriptions', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 9);

    foreach($cluster_metadata as $index => $cluster){
        if($index > 0) {
            $pdf->Ln(3);
        }

        // Cluster Header
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(52, 152, 219);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 7, 'Cluster ' . $cluster['cluster_id'] . ': ' . htmlspecialchars($cluster['cluster_name']), 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 9);

        // Description
        $pdf->MultiCell(0, 4, htmlspecialchars($cluster['description']), 0, 'L');
    }

    // New page for Feature Comparisons and Recommendations
    $pdf->AddPage();

    // 5. CLUSTER FEATURE COMPARISONS
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Cluster Feature Profile Comparison', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Ln(3);

    // Radar Chart - Normalized Features (full page)
    if(file_exists($radarChartFile)){
        $pdf->Image($radarChartFile, 15, $pdf->GetY(), 200, 140);
    }

    // New page for grouped bar chart
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Average Income and Purchase Amount by Cluster', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Ln(3);

    // Grouped Bar Chart - Average Metrics
    if(file_exists($comparisonChartFile)){
        $pdf->Image($comparisonChartFile, 15, $pdf->GetY(), 240, 80);
    }

    $pdf->Ln(5);

    // New page for Recommended Marketing Strategies
    $pdf->AddPage();

    // 6. RECOMMENDED MARKETING STRATEGIES
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Recommended Marketing Strategies', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 9);

    foreach($cluster_metadata as $index => $cluster){
        if($index > 0) {
            $pdf->Ln(3);
        }

        // Cluster Header
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(46, 204, 113);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 7, htmlspecialchars($cluster['cluster_name']) . ' (' . number_format($cluster['customer_count']) . ' customers)', 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 8.5);

        // Recommendations (split by semicolon)
        $recommendations = array_filter(array_map('trim', explode(';', $cluster['business_recommendation'])));
        foreach($recommendations as $rec){
            $pdf->Cell(5, 5, '•', 0, 0);
            $pdf->MultiCell(0, 5, htmlspecialchars($rec), 0, 'L');
        }
    }

    $pdf->Output('export_cluster.pdf', 'D');
}

function exportPdf(array $results, string $segmentationType, string $barChartFile, string $pieChartFile, string $insights) {
    $pdf = new TCPDF('L'); // Landscape
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 10);

    // Title
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Customer Segmentation Analysis Report', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Report Type: ' . ucfirst(str_replace('_', ' ', $segmentationType)), 0, 1, 'L');
    $pdf->Cell(0, 5, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1, 'L');
    $pdf->Ln(5);

    // Analysis Insights Section
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Analysis Insights', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5, $insights, 0, 'L');
    $pdf->Ln(5);

    // Data Table Section
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Customer Segmentation Data', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 8);

    // Table HTML
    $html = '<table border="1" cellpadding="3" style="border-collapse: collapse; table-layout: fixed; width: 100%"><tr>';

    // Headers
    $headers = array_keys($results[0]);
    $colWidth = 100 / count($headers); // percentage width
    foreach($headers as $header){
        $html .= '<th style="width:'.$colWidth.'%; word-wrap: break-word;">'.htmlspecialchars(ucfirst(str_replace('_', ' ', $header))).'</th>';
    }
    $html .= '</tr>';

    // Rows
    foreach($results as $row){
        $html .= '<tr>';
        foreach($row as $cell){
            // truncate long text
            $cellText = strlen($cell) > 80 ? substr($cell,0,80).'...' : $cell;
            $html .= '<td>'.nl2br(htmlspecialchars($cellText)).'</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</table>';

    // Write table
    $pdf->writeHTML($html, true, false, false, false, '');

    // Charts Section
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Visualizations', 0, 1, 'L');

    // Store the Y position before adding charts
    $chartStartY = $pdf->GetY();

    // Embed bar chart on the left (half page width)
    if(file_exists($barChartFile)){
        $pdf->Image($barChartFile, 15, $chartStartY, 120, 65);
    }

    // Embed pie chart on the right (half page width)
    if(file_exists($pieChartFile)){
        $pdf->Image($pieChartFile, 145, $chartStartY, 120, 65);
    }

    $pdf->Output('export_'.$segmentationType.'.pdf', 'D');
}

function exportClvPdf(array $results, string $barChartFile, string $combinationChartFile, string $insights) {
    $pdf = new TCPDF('L'); // Landscape
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 10);

    // Title
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Customer Lifetime Value (CLV) Segmentation Analysis Report', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Report Type: CLV Tiers', 0, 1, 'L');
    $pdf->Cell(0, 5, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1, 'L');
    $pdf->Ln(5);

    // Analysis Insights Section
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'CLV Analysis Insights', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5, $insights, 0, 'L');
    $pdf->Ln(5);

    // CLV Data Table Section
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'CLV Tier Segmentation Data', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 8);

    // Table HTML
    $html = '<table border="1" cellpadding="3" style="border-collapse: collapse; table-layout: fixed; width: 100%"><tr>';
    
    // Headers
    $headers = array_keys($results[0]);
    $colWidth = 100 / count($headers);
    foreach($headers as $header){
        $html .= '<th style="width:'.$colWidth.'%; word-wrap: break-word;">'.htmlspecialchars(ucfirst(str_replace('_', ' ', $header))).'</th>';
    }
    $html .= '</tr>';

    // Rows
    foreach($results as $row){
        $html .= '<tr>';
        foreach($row as $cell){
            $cellText = strlen($cell) > 80 ? substr($cell,0,80).'...' : $cell;
            $html .= '<td>'.nl2br(htmlspecialchars($cellText)).'</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</table>';
    $pdf->writeHTML($html, true, false, false, false, '');

    // Visualizations Section
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'CLV Tier Visualizations', 0, 1, 'L');
    
    $chartStartY = $pdf->GetY();

    // Horizontal Bar Chart
    if(file_exists($barChartFile)){
        $pdf->Image($barChartFile, 15, $chartStartY, 130, 70);
    }

    // Move to next page for combination chart
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'CLV Performance Analysis', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Ln(3);

    // Combination Chart (full width)
    if(file_exists($combinationChartFile)){
        $pdf->Image($combinationChartFile, 15, $pdf->GetY(), 240, 100);
    }

    $pdf->Output('export_clv.pdf', 'D');
}
