<?php
require_once 'db.php';
require_once 'fpdf186/fpdf.php'; // Include the FPDF library
session_start();

if (!isset($_SESSION['LoggedIn']) || !$_SESSION['LoggedIn']) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['UserId'];
$deckId = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    // Validate deck ownership
    $stmt = $conn->prepare("SELECT name FROM decks WHERE id = ? AND account_id = ?");
    $stmt->bind_param("ii", $deckId, $userId);
    $stmt->execute();
    $deck = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$deck) {
        die("Deck not found or access denied.");
    }

    $deckName = $deck['name'];

    // Fetch cards
    $stmt = $conn->prepare("SELECT front, back FROM cards WHERE deck_id = ?");
    $stmt->bind_param("i", $deckId);
    $stmt->execute();
    $result = $stmt->get_result();

    // Initialize FPDF
    $pdf = new FPDF();
    
    $pdf->addFont('Inter', '', 'Inter-VariableFont_opsz,wght.php');
    $pdf->addFont('Inter', 'B', 'Inter_18pt-Bold.php');
    
    $font = 'Inter';
    $pdf->SetFont($font, 'B', 16);
    $pdf->AddPage();

    // Add deck name as title
    $pdf->Cell(0, 10, "Deck: " . $deckName, 0, 1, 'C');
    $pdf->Ln(10);

    // Add cards
    $pdf->SetFont($font, '', 12);
    while ($row = $result->fetch_assoc()) {
        $front = $row['front'];
        $back = $row['back'];

        $pdf->MultiCell(0, 10, $front . " - " . $back);
    }

    $stmt->close();

    // Output the PDF
    $pdf->Output('D', "{$deckName}.pdf");
} catch (Exception $e) {
    die("Error generating PDF: " . $e->getMessage());
}
?>
