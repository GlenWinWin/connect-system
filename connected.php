<!-- connected.php - UPDATED -->
<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireAuth();

// Get the start and end date from URL parameters
$startDate = $_GET['start'] ?? '01';
$endDate = $_GET['end'] ?? date('t');

// Convert start date to month name
$monthName = date('F', mktime(0, 0, 0, $startDate, 1));
$year = date('Y');

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
    <link rel="stylesheet" href="css/main.css">
    <style>
        /* Unique styles NOT in main.css */
        
        /* Connected Table Specific Styles */
        .table-header {
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--secondary) 100%);
            color: white;
            padding: 20px 30px;
            border-radius: var(--border-radius-sm);
            margin-bottom: 20px;
            font-family: 'Poppins', sans-serif;
            font-size: 18px;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(255, 142, 83, 0.2);
        }

        .table-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            padding: 18px 30px;
            border-bottom: 1px solid var(--gray-light);
            transition: var(--transition);
            align-items: center;
        }

        .table-row:last-child {
            border-bottom: none;
        }

        .table-row:hover {
            background: rgba(255, 107, 53, 0.05);
            transform: translateX(5px);
            border-radius: var(--border-radius-sm);
        }

        .vip-name {
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
            letter-spacing: 0.5px;
        }

        .discipler-name {
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            font-weight: 500;
            color: var(--success);
            background: rgba(46, 196, 182, 0.1);
            padding: 8px 16px;
            border-radius: 8px;
            display: inline-block;
        }

        /* Page Title Specific Styles */
        .page-title {
            font-family: 'Poppins', sans-serif;
            font-size: 32px;
            font-weight: 700;
            color: white;
            text-align: center;
            flex-grow: 1;
            margin: 0 20px;
            z-index: 1;
        }

        .month-year {
            font-size: 18px;
            opacity: 0.9;
            font-weight: 400;
            margin-left: 10px;
        }

        /* Stats Banner Specific Styles */
        .stats-banner {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            border: 1px solid rgba(255, 107, 53, 0.1);
            position: relative;
            overflow: hidden;
        }

        .stats-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--orange-gradient);
        }

        .stat-item {
            text-align: center;
            padding: 0 20px;
            border-right: 2px solid var(--gray-light);
        }

        .stat-item:last-child {
            border-right: none;
        }

        .stat-number {
            font-family: 'Poppins', sans-serif;
            font-size: 44px;
            font-weight: 800;
            line-height: 1;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        /* PDF Specific Styles */
        .btn-pdf {
            background: linear-gradient(135deg, var(--success) 0%, #25a898 100%);
            box-shadow: 0 4px 15px rgba(46, 196, 182, 0.2);
        }

        .btn-pdf:hover {
            background: linear-gradient(135deg, #25a898 0%, #1d8c7f 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(46, 196, 182, 0.3);
        }

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

        /* Animation delays for table rows */
        .table-row:nth-child(1) { animation-delay: 0.1s; }
        .table-row:nth-child(2) { animation-delay: 0.2s; }
        .table-row:nth-child(3) { animation-delay: 0.3s; }
        .table-row:nth-child(4) { animation-delay: 0.4s; }
        .table-row:nth-child(5) { animation-delay: 0.5s; }
        .table-row:nth-child(6) { animation-delay: 0.6s; }
        .table-row:nth-child(7) { animation-delay: 0.7s; }
        .table-row:nth-child(8) { animation-delay: 0.8s; }
        .table-row:nth-child(9) { animation-delay: 0.9s; }
        .table-row:nth-child(10) { animation-delay: 1.0s; }

        /* Print Styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }

            .header, .stats-banner, .btn {
                display: none;
            }

            .table-container {
                box-shadow: none;
                border: none;
                padding: 0;
            }

            .table-row {
                border-bottom: 1px solid #000;
            }
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
        <!-- Header - UPDATED TEXT -->
        <div class="header">
            <div class="church-info">
                <div class="logo">
                    <i class="fas fa-handshake"></i>
                </div>
                <!-- Updated header text -->
                <h1 class="church-name">Connected for the month of <?php echo $monthName; ?></h1>
            </div>
            
            <div class="user-info">
                <a href="analytics.php" class="btn">
                    <i class="fas fa-arrow-left"></i> <span class="desktop-only">Back to Analytics</span>
                </a>
                <button id="downloadPdfBtn" class="btn btn-pdf">
                    <i class="fas fa-file-pdf"></i> <span class="desktop-only">Download PDF</span>
                </button>
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
                        <div class="vip-name">
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
                    
                    // Page dimensions
                    const pageWidth = doc.internal.pageSize.getWidth();
                    const pageHeight = doc.internal.pageSize.getHeight();
                    const margin = 15;
                    const contentWidth = pageWidth - (margin * 2);
                    
                    // ========== HEADER SECTION ==========
                    // Church header with background
                    doc.setFillColor(255, 107, 53); // Primary orange
                    doc.rect(0, 0, pageWidth, 30, 'F');
                    
                    // Church name
                    doc.setFont('helvetica', 'bold');
                    doc.setFontSize(20);
                    doc.setTextColor(255, 255, 255);
                    doc.text('RIVER OF GOD CHURCH', pageWidth / 2, 18, null, null, 'center');
                    
                    // Report title
                    doc.setFontSize(18);
                    doc.setTextColor(50, 50, 50);
                    doc.text('Connected Members Report', pageWidth / 2, 40, null, null, 'center');
                    
                    // Month and year subtitle
                    doc.setFontSize(14);
                    doc.setTextColor(100, 100, 100);
                    doc.text('Month of ' + monthName + ' ' + year, pageWidth / 2, 50, null, null, 'center');
                    
                    // ========== STATISTICS SECTION ==========
                    const statsY = 60;
                    const statBoxHeight = 25;
                    
                    // Draw stat boxes
                    const statWidth = contentWidth / 3;
                    
                    // Box 1: Total Connected
                    doc.setDrawColor(255, 107, 53);
                    doc.setFillColor(255, 247, 243);
                    doc.roundedRect(margin, statsY, statWidth - 5, statBoxHeight, 3, 3, 'FD');
                    
                    doc.setFontSize(28);
                    doc.setTextColor(255, 107, 53);
                    doc.text(totalConnected.toString(), margin + (statWidth/2) - 10, statsY + 15, null, null, 'center');
                    
                    doc.setFontSize(9);
                    doc.setTextColor(100, 100, 100);
                    doc.text('TOTAL CONNECTED', margin + (statWidth/2) - 10, statsY + 22, null, null, 'center');
                    
                    // Box 2: Month
                    doc.setDrawColor(255, 159, 28);
                    doc.setFillColor(255, 250, 243);
                    doc.roundedRect(margin + statWidth, statsY, statWidth - 5, statBoxHeight, 3, 3, 'FD');
                    
                    doc.setFontSize(22);
                    doc.setTextColor(255, 159, 28);
                    doc.text(monthName, margin + statWidth + (statWidth/2) - 10, statsY + 15, null, null, 'center');
                    
                    doc.setFontSize(9);
                    doc.setTextColor(100, 100, 100);
                    doc.text('MONTH', margin + statWidth + (statWidth/2) - 10, statsY + 22, null, null, 'center');
                    
                    // Box 3: Year
                    doc.setDrawColor(46, 196, 182);
                    doc.setFillColor(242, 253, 252);
                    doc.roundedRect(margin + (statWidth * 2), statsY, statWidth - 5, statBoxHeight, 3, 3, 'FD');
                    
                    doc.setFontSize(22);
                    doc.setTextColor(46, 196, 182);
                    doc.text(year.toString(), margin + (statWidth * 2) + (statWidth/2) - 10, statsY + 15, null, null, 'center');
                    
                    doc.setFontSize(9);
                    doc.setTextColor(100, 100, 100);
                    doc.text('YEAR', margin + (statWidth * 2) + (statWidth/2) - 10, statsY + 22, null, null, 'center');
                    
                    // ========== TABLE SECTION ==========
                    const tableStartY = statsY + statBoxHeight + 20;
                    
                    // Table header
                    doc.setFillColor(255, 107, 53);
                    doc.roundedRect(margin, tableStartY, contentWidth, 10, 2, 2, 'F');
                    
                    doc.setFont('helvetica', 'bold');
                    doc.setFontSize(11);
                    doc.setTextColor(255, 255, 255);
                    doc.text('VIP', margin + 10, tableStartY + 6.5);
                    doc.text('DISCIPLER', margin + 120, tableStartY + 6.5);
                    
                    // Table rows
                    let currentY = tableStartY + 15;
                    doc.setFont('helvetica', 'normal');
                    doc.setFontSize(10);
                    
                    // Draw table grid
                    doc.setDrawColor(200, 200, 200);
                    doc.setLineWidth(0.2);
                    
                    // Draw horizontal lines for each row
                    for(let i = 0; i <= connectedData.length; i++) {
                        const lineY = tableStartY + 10 + (i * 8);
                        if(lineY < pageHeight - 30) {
                            doc.line(margin, lineY, margin + contentWidth, lineY);
                        }
                    }
                    
                    // Draw vertical lines
                    doc.line(margin + 110, tableStartY, margin + 110, Math.min(tableStartY + 10 + (connectedData.length * 8), pageHeight - 30));
                    doc.line(margin, tableStartY, margin, Math.min(tableStartY + 10 + (connectedData.length * 8), pageHeight - 30));
                    doc.line(margin + contentWidth, tableStartY, margin + contentWidth, Math.min(tableStartY + 10 + (connectedData.length * 8), pageHeight - 30));
                    
                    // Add data rows
                    connectedData.forEach((row, index) => {
                        // Check for page break
                        if (currentY > pageHeight - 30) {
                            addPageFooter(doc, pageWidth, pageHeight, margin, generationDate);
                            doc.addPage();
                            
                            // Reset Y position and redraw table header
                            currentY = margin + 10;
                            
                            // Draw new page header
                            doc.setFontSize(10);
                            doc.setTextColor(150, 150, 150);
                            doc.text('Connected Members Report - ' + monthName + ' ' + year, margin, currentY);
                            currentY += 15;
                            
                            // Table header for new page
                            doc.setFillColor(255, 107, 53);
                            doc.roundedRect(margin, currentY, contentWidth, 10, 2, 2, 'F');
                            doc.setFont('helvetica', 'bold');
                            doc.setFontSize(11);
                            doc.setTextColor(255, 255, 255);
                            doc.text('VIP', margin + 10, currentY + 6.5);
                            doc.text('DISCIPLER', margin + 120, currentY + 6.5);
                            
                            currentY += 15;
                            
                            // Redraw grid for new page
                            doc.setDrawColor(200, 200, 200);
                        }
                        
                        // // Alternate row background
                        // if (index % 2 === 0) {
                        //     doc.setFillColor(248, 248, 248);
                        //     doc.rect(margin + 1, currentY - 6, contentWidth - 2, 7, 'F');
                        // }
                        
                        // VIP name (truncate if too long)
                        const vipName = row.vips.length > 40 ? row.vips.substring(0, 37) + '...' : row.vips;
                        doc.setFont('helvetica', 'bold');
                        doc.setFontSize(9);
                        doc.setTextColor(50, 50, 50);
                        doc.text(vipName, margin + 5, currentY);
                        
                        // Discipler name (truncate if too long)
                        const disciplerName = row.discipler.length > 25 ? row.discipler.substring(0, 22) + '...' : row.discipler;
                        doc.setFont('helvetica', 'normal');
                        doc.setFontSize(9);
                        doc.setTextColor(50, 50, 50); // Success color
                        doc.text(disciplerName, margin + 115, currentY);
                        
                        currentY += 8;
                    });
                    
                    // ========== FOOTER SECTION ==========
                    addPageFooter(doc, pageWidth, pageHeight, margin, generationDate);
                    
                    // ========== SAVE PDF ==========
                    const fileName = 'connected_report_' + monthName.toLowerCase() + '_' + year + '.pdf';
                    doc.save(fileName);
                    
                } catch (error) {
                    console.error('PDF Generation Error:', error);
                    alert('Error generating PDF. Please try again.');
                } finally {
                    // Hide loading overlay
                    loadingOverlay.classList.remove('active');
                }
            }, 100);
        }

        function addPageFooter(doc, pageWidth, pageHeight, margin, generationDate) {
            const currentPage = doc.internal.getCurrentPageInfo().pageNumber;
            const totalPages = doc.internal.getNumberOfPages();
            
            // Footer background
            doc.setFillColor(245, 245, 245);
            doc.rect(0, pageHeight - 20, pageWidth, 20, 'F');
            
            // Footer text
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(8);
            doc.setTextColor(100, 100, 100);
            
            // Left: Generation date
            doc.text('Generated: ' + generationDate, margin, pageHeight - 12);
            
            // Center: Church info
            doc.text('', pageWidth / 2, pageHeight - 12, null, null, 'center');
            
            // Right: Page number
            doc.text('River of God Church - Discipleship Ministry', pageWidth - margin, pageHeight - 12, null, null, 'right');
            
            // Footer line
            doc.setDrawColor(255, 107, 53);
            doc.setLineWidth(0.5);
            doc.line(margin, pageHeight - 20, pageWidth - margin, pageHeight - 20);
        }
    </script>
</body>
</html>