<!-- connected.php -->
<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireAuth();

// Get the start and end date from URL parameters
$startDate = $_GET['start'] ?? '01';
$endDate = $_GET['end'] ?? date('t'); // Default to last day of current month

// Convert start date to month name
$monthName = date('F', mktime(0, 0, 0, $startDate, 1));
$year = date('Y');

// If start is numeric (like "01"), it means January
if (is_numeric($startDate) && $startDate >= 1 && $startDate <= 12) {
    $monthName = date('F', mktime(0, 0, 0, $startDate, 1));
    $year = date('Y');
}

// Fetch connected data from database
$sql = "SELECT 
            UPPER(fullname) as vips,
            UPPER(connected_with) as discipler
        FROM first_timers 
        WHERE started_one2one = 1 
        AND MONTH(created_at) = ? 
        AND YEAR(created_at) = ?
        ORDER BY fullname";

$stmt = $pdo->prepare($sql);
$stmt->execute([$startDate, $year]);
$connectedData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalConnected = count($connectedData);

// Prepare data for JavaScript
$connectedDataJson = json_encode($connectedData);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connected (One-to-One) - River of God Church</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Include jsPDF Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    
    <style>
        :root {
            --primary: #FF6B35;
            --primary-light: #FF8E53;
            --primary-dark: #E55A2B;
            --secondary: #FF9F1C;
            --accent: #FF5A5F;
            --success: #2EC4B6;
            --warning: #FFBF69;
            --info: #3A86FF;
            --light: #FFF8F0;
            --dark: #2A2D34;
            --gray: #8C8C8C;
            --gray-light: #F5F3F4;
            --orange-gradient: linear-gradient(135deg, #FF6B35 0%, #FF9F1C 100%);
            --warm-gradient: linear-gradient(135deg, #FF8E53 0%, #FFBF69 100%);
            --border-radius: 16px;
            --border-radius-sm: 10px;
            --shadow: 0 8px 30px rgba(255, 107, 53, 0.12);
            --shadow-lg: 0 15px 50px rgba(255, 107, 53, 0.18);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ... (your existing CSS remains the same) ... */

        /* Add PDF button style */
        .btn-pdf {
            background: linear-gradient(135deg, var(--success) 0%, #25a898 100%);
            box-shadow: 0 4px 15px rgba(46, 196, 182, 0.2);
        }

        .btn-pdf:hover {
            background: linear-gradient(135deg, #25a898 0%, #1d8c7f 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(46, 196, 182, 0.3);
        }

        /* Loading overlay for PDF generation */
        .pdf-loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            color: white;
        }

        .pdf-loading-overlay.active {
            display: flex;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: var(--success);
            animation: spin 1s ease-in-out infinite;
            margin-bottom: 20px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div id="pdfLoadingOverlay" class="pdf-loading-overlay">
        <div class="spinner"></div>
        <h3>Generating PDF Report...</h3>
        <p>Please wait a moment</p>
    </div>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="church-info">
                <div class="logo">
                    <i class="fas fa-handshake"></i>
                </div>
                <h1 class="church-name">Connected for the month <?php echo $monthName; ?></h1>
            </div>
            
            <div class="user-info">
                <!-- Download PDF Button -->
                <button id="downloadPdfBtn" class="btn btn-pdf">
                    <i class="fas fa-file-pdf"></i> <span class="desktop-only">Download PDF</span>
                </button>
                
                <a href="analytics.php" class="btn">
                    <i class="fas fa-arrow-left"></i> <span class="desktop-only">Back to Analytics</span>
                </a>
            </div>
        </div>

        <!-- Stats Banner -->
        <div class="stats-banner">
            <div class="stat-item">
                <div class="stat-number" id="statTotal"><?php echo $totalConnected; ?></div>
                <div class="stat-label">Total Connected</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" id="statMonth"><?php echo $monthName; ?></div>
                <div class="stat-label">Month</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" id="statYear"><?php echo $year; ?></div>
                <div class="stat-label">Year</div>
            </div>
        </div>

        <?php if (empty($connectedData)): ?>
            <div class="empty-state">
                <i class="fas fa-users-slash"></i>
                <h3>No Connected Members</h3>
                <p>There are no one-to-one connections for <?php echo $monthName . ' ' . $year; ?>.</p>
            </div>
        <?php else: ?>
            <!-- Connected Table -->
            <div class="table-container" id="reportContent">
                <div class="table-header">
                    <div>VIPS</div>
                    <div>DISCIPLER</div>
                </div>
                
                <?php foreach ($connectedData as $row): ?>
                    <div class="table-row">
                        <div class="vip-name">
                            <?php echo htmlspecialchars($row['vips']); ?>
                        </div>
                        <div class="discipler-name">
                            <?php echo htmlspecialchars($row['discipler']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Store data from PHP for JavaScript use
        const connectedData = <?php echo $connectedDataJson; ?>;
        const monthName = "<?php echo $monthName; ?>";
        const year = "<?php echo $year; ?>";
        const totalConnected = <?php echo $totalConnected; ?>;
        const generationDate = new Date().toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        document.addEventListener('DOMContentLoaded', function() {
            const downloadPdfBtn = document.getElementById('downloadPdfBtn');
            const loadingOverlay = document.getElementById('pdfLoadingOverlay');
            
            downloadPdfBtn.addEventListener('click', generatePDF);
            
            // Your existing hover effects
            const tableRows = document.querySelectorAll('.table-row');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    const vipName = this.querySelector('.vip-name');
                    const disciplerName = this.querySelector('.discipler-name');
                    
                    vipName.style.transform = 'translateX(10px)';
                    vipName.style.transition = 'transform 0.3s ease';
                    
                    disciplerName.style.backgroundColor = 'rgba(46, 196, 182, 0.2)';
                    disciplerName.style.boxShadow = '0 4px 15px rgba(46, 196, 182, 0.2)';
                });
                
                row.addEventListener('mouseleave', function() {
                    const vipName = this.querySelector('.vip-name');
                    const disciplerName = this.querySelector('.discipler-name');
                    
                    vipName.style.transform = 'translateX(0)';
                    
                    disciplerName.style.backgroundColor = 'rgba(46, 196, 182, 0.1)';
                    disciplerName.style.boxShadow = 'none';
                });
            });
        });

        function generatePDF() {
            // Show loading overlay
            const loadingOverlay = document.getElementById('pdfLoadingOverlay');
            loadingOverlay.classList.add('active');
            
            // Use setTimeout to allow UI to update
            setTimeout(() => {
                try {
                    // Initialize jsPDF
                    const { jsPDF } = window.jspdf;
                    const doc = new jsPDF('portrait', 'mm', 'a4');
                    
                    // Set document properties
                    doc.setProperties({
                        title: 'Connected Members Report - ' + monthName + ' ' + year,
                        subject: 'One-to-One Discipleship Connections',
                        author: 'River of God Church',
                        keywords: 'discipleship, connections, report',
                        creator: 'Church Analytics System'
                    });
                    
                    // Page dimensions
                    const pageWidth = doc.internal.pageSize.getWidth();
                    const pageHeight = doc.internal.pageSize.getHeight();
                    const margin = 15;
                    const contentWidth = pageWidth - (margin * 2);
                    
                    // Add header with background
                    doc.setFillColor(255, 107, 53); // Primary orange
                    doc.rect(0, 0, pageWidth, 40, 'F');
                    
                    // Church logo/icon
                    doc.setFontSize(28);
                    doc.setTextColor(255, 255, 255);
                    doc.text('🤝', 20, 25);
                    
                    // Church name
                    doc.setFont('helvetica', 'bold');
                    doc.setFontSize(20);
                    doc.text('RIVER OF GOD CHURCH', 40, 25);
                    
                    // Report title
                    doc.setFontSize(16);
                    doc.text('Connected Members Report', pageWidth / 2, 55, null, null, 'center');
                    
                    // Month and year
                    doc.setFontSize(14);
                    doc.setTextColor(100, 100, 100);
                    doc.text(monthName + ' ' + year, pageWidth / 2, 65, null, null, 'center');
                    
                    // Statistics box
                    const statsY = 75;
                    doc.setDrawColor(255, 107, 53);
                    doc.setLineWidth(0.5);
                    doc.rect(margin, statsY, contentWidth, 20);
                    
                    // Total connected
                    doc.setFont('helvetica', 'bold');
                    doc.setFontSize(24);
                    doc.setTextColor(255, 107, 53);
                    doc.text(totalConnected.toString(), margin + 20, statsY + 14);
                    
                    doc.setFontSize(10);
                    doc.setTextColor(100, 100, 100);
                    doc.text('TOTAL CONNECTED', margin + 20, statsY + 19);
                    
                    // Month
                    doc.setFontSize(18);
                    doc.setTextColor(255, 159, 28);
                    doc.text(monthName, margin + 80, statsY + 14);
                    
                    doc.setFontSize(10);
                    doc.setTextColor(100, 100, 100);
                    doc.text('MONTH', margin + 80, statsY + 19);
                    
                    // Year
                    doc.setFontSize(18);
                    doc.setTextColor(46, 196, 182);
                    doc.text(year.toString(), margin + 140, statsY + 14);
                    
                    doc.setFontSize(10);
                    doc.setTextColor(100, 100, 100);
                    doc.text('YEAR', margin + 140, statsY + 19);
                    
                    // Table header
                    const tableStartY = statsY + 30;
                    doc.setFillColor(255, 107, 53);
                    doc.rect(margin, tableStartY, contentWidth, 10, 'F');
                    
                    doc.setFont('helvetica', 'bold');
                    doc.setFontSize(12);
                    doc.setTextColor(255, 255, 255);
                    doc.text('VIP', margin + 5, tableStartY + 7);
                    doc.text('DISCIPLER', margin + 120, tableStartY + 7);
                    
                    // Table rows
                    let currentY = tableStartY + 15;
                    doc.setFont('helvetica', 'normal');
                    doc.setFontSize(11);
                    
                    connectedData.forEach((row, index) => {
                        // Alternate row colors
                        if (index % 2 === 0) {
                            doc.setFillColor(248, 248, 248);
                            doc.rect(margin, currentY - 4, contentWidth, 8, 'F');
                        }
                        
                        // VIP name
                        doc.setTextColor(50, 50, 50);
                        doc.setFont('helvetica', 'bold');
                        doc.text(row.vips.substring(0, 40), margin + 5, currentY);
                        
                        // Discipler name
                        doc.setTextColor(46, 196, 182); // Success color
                        doc.setFont('helvetica', 'normal');
                        doc.text(row.discipler.substring(0, 30), margin + 120, currentY);
                        
                        currentY += 8;
                        
                        // Check for page break
                        if (currentY > pageHeight - 30) {
                            doc.addPage();
                            currentY = margin + 10;
                            
                            // Add header on new page
                            doc.setFontSize(10);
                            doc.setTextColor(150, 150, 150);
                            doc.text('Connected Members Report - ' + monthName + ' ' + year, margin, currentY - 5);
                        }
                    });
                    
                    // Footer
                    const footerY = pageHeight - 15;
                    doc.setFontSize(10);
                    doc.setTextColor(150, 150, 150);
                    doc.text('Generated on: ' + generationDate, margin, footerY);
                    doc.text('Page ' + doc.internal.getNumberOfPages(), pageWidth - margin, footerY, null, null, 'right');
                    
                    // Church footer
                    doc.setFont('helvetica', 'bold');
                    doc.setTextColor(255, 107, 53);
                    doc.text('River of God Church - Discipleship Department', pageWidth / 2, footerY - 10, null, null, 'center');
                    
                    // Save the PDF
                    const fileName = 'connected_report_' + monthName.toLowerCase() + '_' + year + '.pdf';
                    doc.save(fileName);
                    
                } catch (error) {
                    console.error('PDF Generation Error:', error);
                    alert('Error generating PDF. Please try again or use the print function.');
                } finally {
                    // Hide loading overlay
                    loadingOverlay.classList.remove('active');
                }
            }, 100);
        }

        // Alternative: Generate PDF from HTML content (simpler but less control)
        function generatePDFFromHTML() {
            const { jsPDF } = window.jspdf;
            
            // Create a temporary div with print styles
            const printDiv = document.createElement('div');
            printDiv.innerHTML = `
                <div style="font-family: Arial, sans-serif; padding: 20px;">
                    <h1 style="color: #FF6B35; text-align: center;">River of God Church</h1>
                    <h2 style="text-align: center;">Connected Members Report</h2>
                    <p style="text-align: center; color: #666;">${monthName} ${year}</p>
                    
                    <div style="display: flex; justify-content: space-around; margin: 20px 0; padding: 15px; background: #f5f5f5;">
                        <div style="text-align: center;">
                            <div style="font-size: 24px; font-weight: bold; color: #FF6B35;">${totalConnected}</div>
                            <div style="font-size: 12px; color: #666;">Total Connected</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 24px; font-weight: bold; color: #FF9F1C;">${monthName}</div>
                            <div style="font-size: 12px; color: #666;">Month</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 24px; font-weight: bold; color: #2EC4B6;">${year}</div>
                            <div style="font-size: 12px; color: #666;">Year</div>
                        </div>
                    </div>
                    
                    <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                        <thead>
                            <tr style="background: #FF6B35; color: white;">
                                <th style="padding: 10px; text-align: left;">VIP</th>
                                <th style="padding: 10px; text-align: left;">DISCIPLER</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${connectedData.map(row => `
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 8px 10px;">${row.vips}</td>
                                    <td style="padding: 8px 10px; color: #2EC4B6; font-weight: 500;">${row.discipler}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 40px; text-align: center; font-size: 12px; color: #888; border-top: 1px solid #eee; padding-top: 10px;">
                        Generated on: ${new Date().toLocaleDateString()} | River of God Church
                    </div>
                </div>
            `;
            
            document.body.appendChild(printDiv);
            
            // Use jsPDF HTML method (requires html2canvas)
            const doc = new jsPDF();
            doc.html(printDiv, {
                callback: function(doc) {
                    doc.save('connected_report_' + monthName.toLowerCase() + '_' + year + '.pdf');
                    document.body.removeChild(printDiv);
                },
                x: 10,
                y: 10,
                width: 190,
                windowWidth: 800
            });
        }
    </script>
</body>
</html>