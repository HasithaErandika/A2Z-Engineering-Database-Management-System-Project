<?php
// Include the TCPDF library
require_once('tcpdf/tcpdf.php');

// Ensure report content is available via POST
if (isset($_POST['report_output'])) {
    $report_output = $_POST['report_output'];

    // Debugging: Output the raw report content
    echo '<pre>' . htmlspecialchars($report_output) . '</pre>';
    exit; // Exit the script here so you can inspect the content

    // Create a new instance of TCPDF
    $pdf = new TCPDF();

    // Set document information
    $pdf->SetCreator('Your Company');
    $pdf->SetTitle('Salary Slip');
    $pdf->SetSubject('Employee Salary Report');

    // Remove default header and footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Add a page
    $pdf->AddPage();

    // Set font for the PDF
    $pdf->SetFont('helvetica', '', 12);

    // Write the HTML content to the PDF
    $pdf->writeHTML($report_output, true, false, true, false, '');

    // Save the PDF to a string
    $pdfOutput = $pdf->Output('salary_slip.pdf', 'S'); // 'S' saves the file as a string

    // Encode the PDF in Base64 to send back as a response
    echo base64_encode($pdfOutput);
} else {
    echo "No report content found!";
}
?>
