<?php
// seed_math_g6.php — Seeds 150 lessons for Grade 6 Mathematics
// Step 1: Create the learning area in curriculum.php (Grade 6, "Mathematics", code "MATH", 5 lessons/week)
// Step 2: Note the learning area ID, then visit:
//         http://localhost/SCHEME/seed_math_g6.php?la=YOUR_ID
// Add &force=1 to wipe and re-seed.
require_once 'config.php';
$pdo = getDB();

$learningAreaId = isset($_GET['la']) ? (int)$_GET['la'] : 0;

if ($learningAreaId < 1) {
    // Show available learning areas for convenience
    $areas = $pdo->query(
        "SELECT la.id, la.name, la.short_code, g.name AS grade_name
         FROM learning_areas la JOIN grades g ON g.id = la.grade_id
         ORDER BY g.sort_order, la.name"
    )->fetchAll();
    echo "<style>body{font-family:Segoe UI,sans-serif;padding:30px} table{border-collapse:collapse;width:100%;max-width:600px} th,td{border:1px solid #d1d5db;padding:8px 12px} th{background:#1a56db;color:#fff} a{color:#1a56db}</style>";
    echo "<h2>Grade 6 Mathematics Seed</h2>";
    echo "<p style='color:red'>Please provide a learning area ID: <code>seed_math_g6.php?la=ID</code></p>";
    echo "<p>First <a href='curriculum.php'>create the learning area</a> for Grade 6 Mathematics, then use its ID below.</p>";
    echo "<table><tr><th>ID</th><th>Grade</th><th>Learning Area</th><th>Code</th><th>Use</th></tr>";
    foreach ($areas as $a) {
        echo "<tr><td>{$a['id']}</td><td>" . htmlspecialchars($a['grade_name']) . "</td><td>" . htmlspecialchars($a['name']) . "</td><td>" . htmlspecialchars($a['short_code'] ?? '') . "</td>";
        echo "<td><a href='seed_math_g6.php?la={$a['id']}'>Seed here</a></td></tr>";
    }
    echo "</table>";
    exit;
}

// Verify learning area exists
$laStmt = $pdo->prepare("SELECT la.*, g.name AS grade_name FROM learning_areas la JOIN grades g ON g.id = la.grade_id WHERE la.id = :id");
$laStmt->execute([':id' => $learningAreaId]);
$la = $laStmt->fetch();
if (!$la) {
    echo "<p style='color:red'>Learning area ID $learningAreaId not found. <a href='seed_math_g6.php'>Go back</a></p>"; exit;
}

$count = $pdo->prepare("SELECT COUNT(*) FROM scheme_of_work WHERE learning_area_id = :id");
$count->execute([':id' => $learningAreaId]);
$count = $count->fetchColumn();
if ($count > 0 && empty($_GET['force'])) {
    echo "<p style='color:orange;font-weight:bold'>Already has $count records.<br>
          <a href='seed_math_g6.php?la=$learningAreaId&force=1' onclick=\"return confirm('Delete all rows for this learning area and re-seed?')\">Force re-seed</a> |
          <a href='index.php?learning_area_id=$learningAreaId'>View SOW</a></p>";
    exit;
}
if ($count > 0) {
    $pdo->prepare("DELETE FROM scheme_of_work WHERE learning_area_id = :id")->execute([':id' => $learningAreaId]);
}

$LESSONS_PER_WEEK = (int)$la['lessons_per_week'];
if ($LESSONS_PER_WEEK < 1) $LESSONS_PER_WEEK = 5;

// ── Grade 6 Mathematics — Full CBC Lesson Data ──────────────────────────────
$lessons = [

  // ═══════════════════════════════════════════════════════════════════════
  // 1.0 NUMBERS — 1.1 Whole Numbers (10 Lessons)
  // ═══════════════════════════════════════════════════════════════════════
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.1 Whole Numbers',
    'slo_cd'      => 'use place value and total value of digits up to millions in real life',
    'slo_sow'     => "Lesson 1:\na) Identify the place value of digits up to millions in a given number.\nb) State the position of any digit in a seven-digit number.",
    'le_cd'       => 'use place value and total value of digits up to millions in real life',
    'le_sow'      => "* Engage in an interactive demonstration of place value by responding to guiding questions and completing steps using the chalkboard or tablet.\n* Perform or solve place value exercises in exercise books individually using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we identify the place value of digits up to millions?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.1 Whole Numbers',
    'slo_cd'      => 'use place value and total value of digits up to millions',
    'slo_sow'     => "Lesson 2:\na) Determine the total value of digits in numbers up to millions.\nb) Calculate the value of specific digits within a seven-digit figure.",
    'le_cd'       => 'use place value and total value of digits up to millions',
    'le_sow'      => "* Engage in an interactive demonstration of total value by responding to guiding questions and completing steps.\n* Perform or solve total value exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we determine the total value of digits in numbers up to millions?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.1 Whole Numbers',
    'slo_cd'      => 'read and write numbers up to 100,000 in words in real life',
    'slo_sow'     => "Lesson 3:\na) Read numbers up to 100,000 in words correctly.\nb) Write given figures up to 100,000 in word form with correct spelling.",
    'le_cd'       => 'read and write numbers up to 100,000 in words in real life',
    'le_sow'      => "* Engage in an interactive demonstration of reading and writing numbers by responding to guiding questions and completing steps.\n* Perform or solve writing exercises individually in exercise books using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we read and write numbers up to 100,000 in words?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.1 Whole Numbers',
    'slo_cd'      => 'order numbers up to 100,000 in real-life situations',
    'slo_sow'     => "Lesson 4:\na) Compare numbers up to 100,000 using symbols (<, >).\nb) Arrange a set of numbers up to 100,000 in ascending order.",
    'le_cd'       => 'order numbers up to 100,000 in real-life situations',
    'le_sow'      => "* Engage in an interactive demonstration of ascending order by responding to guiding questions and completing steps.\n* Perform or solve ordering exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we compare and arrange numbers up to 100,000 in ascending order?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.1 Whole Numbers',
    'slo_cd'      => 'order numbers up to 100,000',
    'slo_sow'     => "Lesson 5:\na) Arrange a set of numbers up to 100,000 in descending order.\nb) Identify the largest and smallest numbers in a provided set.",
    'le_cd'       => 'order numbers up to 100,000',
    'le_sow'      => "* Engage in an interactive demonstration of descending order by responding to guiding questions and completing steps.\n* Perform or solve ordering exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we arrange numbers up to 100,000 in descending order?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.1 Whole Numbers',
    'slo_cd'      => 'round off numbers up to 100,000 to the nearest thousand',
    'slo_sow'     => "Lesson 6:\na) Identify the thousands digit and the rounding digit (hundreds).\nb) Round off whole numbers up to 100,000 to the nearest thousands.",
    'le_cd'       => 'round off numbers up to 100,000 to the nearest thousand',
    'le_sow'      => "* Engage in an interactive demonstration of rounding to the nearest thousand by responding to guiding questions and completing steps.\n* Perform or solve rounding exercises individually in exercise books using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we round off numbers up to 100,000 to the nearest thousand?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.1 Whole Numbers',
    'slo_cd'      => 'round off numbers up to 100,000',
    'slo_sow'     => "Lesson 7:\na) Identify the tens of thousand digit.\nb) Round off whole numbers up to 100,000 to the nearest tens of thousand.",
    'le_cd'       => 'round off numbers up to 100,000',
    'le_sow'      => "* Engage in an interactive demonstration of rounding to the tens of thousands by responding to guiding questions and completing steps.\n* Perform or solve rounding exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we round off numbers up to 100,000 to the nearest tens of thousand?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.1 Whole Numbers',
    'slo_cd'      => 'apply squares of whole numbers up to 100',
    'slo_sow'     => "Lesson 8:\na) Multiply a whole number by itself to find its square.\nb) Calculate the squares of whole numbers up to 100 accurately.",
    'le_cd'       => 'apply squares of whole numbers up to 100',
    'le_sow'      => "* Engage in an interactive demonstration of squares by responding to guiding questions and completing steps.\n* Perform or solve square exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we calculate the squares of whole numbers up to 100?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.1 Whole Numbers',
    'slo_cd'      => 'apply square roots of perfect squares up to 10,000',
    'slo_sow'     => "Lesson 9:\na) Identify the square root of perfect squares up to 10,000.\nb) Use the factor tree or inverse method to find square roots.",
    'le_cd'       => 'apply square roots of perfect squares up to 10,000',
    'le_sow'      => "* Engage in an interactive demonstration of square roots by responding to guiding questions and completing steps.\n* Perform or solve square root exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we find the square roots of perfect squares up to 10,000?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.1 Whole Numbers',
    'slo_cd'      => 'appreciate use of whole numbers in real-life situations',
    'slo_sow'     => "Lesson 10:\na) Solve real-life problems involving whole numbers (place value, rounding, and squares).\nb) Evaluate personal progress through a summary exercise.",
    'le_cd'       => 'appreciate use of whole numbers in real-life situations',
    'le_sow'      => "* Engage in an interactive review of whole numbers by responding to guiding questions and completing steps.\n* Perform or solve a summary assessment exercise individually using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we apply whole numbers to solve real-life problems?',
  ],

  // ═══════════════════════════════════════════════════════════════════════
  // 1.0 NUMBERS — 1.2 Multiplication (4 Lessons)
  // ═══════════════════════════════════════════════════════════════════════
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.2 Multiplication',
    'slo_cd'      => 'multiply up to a 4-digit number by a 2-digit number in real-life situations',
    'slo_sow'     => "Lesson 1:\na) Multiply a 4-digit number by a 1-digit number accurately.",
    'le_cd'       => 'multiply up to a 4-digit number by a 2-digit number in real-life situations',
    'le_sow'      => "* Engage in an interactive demonstration of multiplication by responding to guiding questions and completing steps.\n* Perform or solve multiplication exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we multiply a 4-digit number by a 1-digit number?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.2 Multiplication',
    'slo_cd'      => 'multiply up to a 4-digit number by a 2-digit number',
    'slo_sow'     => "Lesson 2:\na) Multiply a 4-digit number by a 2-digit number using long multiplication.",
    'le_cd'       => 'multiply up to a 4-digit number by a 2-digit number',
    'le_sow'      => "* Engage in an interactive demonstration of multiplication by responding to guiding questions and completing steps.\n* Perform or solve multiplication exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we multiply a 4-digit number by a 2-digit number using long multiplication?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.2 Multiplication',
    'slo_cd'      => 'estimate products by rounding off numbers being multiplied to the nearest ten',
    'slo_sow'     => "Lesson 3:\na) Estimate products using the compatibility of numbers.",
    'le_cd'       => 'estimate products by rounding off numbers being multiplied to the nearest ten',
    'le_sow'      => "* Engage in an interactive demonstration of multiplication by responding to guiding questions and completing steps.\n* Perform or solve multiplication exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we estimate products by rounding off numbers to the nearest ten?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.2 Multiplication',
    'slo_cd'      => 'make patterns involving multiplication of numbers not exceeding 1,000',
    'slo_sow'     => "Lesson 4:\na) Create patterns involving multiplication of numbers not exceeding 1,000.",
    'le_cd'       => 'make patterns involving multiplication of numbers not exceeding 1,000',
    'le_sow'      => "* Engage in an interactive demonstration of multiplication patterns by responding to guiding questions and completing steps.\n* Perform or solve pattern exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we create patterns involving multiplication of numbers up to 1,000?',
  ],

  // ═══════════════════════════════════════════════════════════════════════
  // 1.0 NUMBERS — 1.3 Division (6 Lessons)
  // ═══════════════════════════════════════════════════════════════════════
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.3 Division',
    'slo_cd'      => 'divide up to a 4-digit number by up to a 3-digit number where the dividend is greater than the divisor',
    'slo_sow'     => "Lesson 1:\na) Identify the steps of dividing a 4-digit number by a 2-digit number.\nb) Divide a 4-digit number by a 2-digit number without a remainder.",
    'le_cd'       => 'divide up to a 4-digit number by up to a 3-digit number where the dividend is greater than the divisor',
    'le_sow'      => "* Engage in an interactive demonstration of division by responding to guiding questions and completing steps.\n* Perform or solve division exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we divide a 4-digit number by a 2-digit number without a remainder?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.3 Division',
    'slo_cd'      => 'divide up to a 4-digit number by up to a 3-digit number',
    'slo_sow'     => "Lesson 2:\na) Divide a 4-digit number by a 2-digit number with a remainder.\nb) State the quotient and the remainder correctly.",
    'le_cd'       => 'divide up to a 4-digit number by up to a 3-digit number',
    'le_sow'      => "* Engage in an interactive demonstration of division by responding to guiding questions and completing steps.\n* Perform or solve division exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we divide a 4-digit number by a 2-digit number with a remainder?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.3 Division',
    'slo_cd'      => 'divide up to a 4-digit number by up to a 3-digit number',
    'slo_sow'     => "Lesson 3:\na) Divide a 4-digit number by a 3-digit number without a remainder.\nb) Work out the steps of long division for 3-digit divisors accurately.",
    'le_cd'       => 'divide up to a 4-digit number by up to a 3-digit number',
    'le_sow'      => "* Engage in an interactive demonstration of division by responding to guiding questions and completing steps.\n* Perform or solve division exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we divide a 4-digit number by a 3-digit number without a remainder?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.3 Division',
    'slo_cd'      => 'divide up to a 4-digit number by up to a 3-digit number',
    'slo_sow'     => "Lesson 4:\na) Divide a 4-digit number by a 3-digit number with a remainder.\nb) Interpret remainders in real-life division scenarios.",
    'le_cd'       => 'divide up to a 4-digit number by up to a 3-digit number',
    'le_sow'      => "* Engage in an interactive demonstration of division by responding to guiding questions and completing steps.\n* Perform or solve division exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we divide a 4-digit number by a 3-digit number with a remainder?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.3 Division',
    'slo_cd'      => 'estimate quotients by rounding off the dividend and divisor',
    'slo_sow'     => "Lesson 5:\na) Round off the dividend and divisor to the nearest ten.\nb) Estimate quotients by dividing the rounded numbers.",
    'le_cd'       => 'estimate quotients by rounding off the dividend and divisor',
    'le_sow'      => "* Engage in an interactive demonstration of division by responding to guiding questions and completing steps.\n* Perform or solve division exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we estimate quotients by rounding off the dividend and divisor?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.3 Division',
    'slo_cd'      => 'perform combined operations up to 3-digit number',
    'slo_sow'     => "Lesson 6:\na) Identify the order of combined operations (BODMAS/PEMDAS).\nb) Perform combined operations involving addition, subtraction, multiplication, and division.",
    'le_cd'       => 'perform combined operations up to 3-digit number',
    'le_sow'      => "* Engage in an interactive demonstration of division by responding to guiding questions and completing steps.\n* Perform or solve division exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we perform combined operations using BODMAS?',
  ],

  // ═══════════════════════════════════════════════════════════════════════
  // 1.0 NUMBERS — 1.4 Fractions (8 Lessons)
  // ═══════════════════════════════════════════════════════════════════════
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.4 Fractions',
    'slo_cd'      => 'add fractions using LCM in different situations',
    'slo_sow'     => "Lesson 1:\na) Define the term Least Common Multiple (LCM).\nb) Identify the LCM of denominators in given sets of fractions.",
    'le_cd'       => 'add fractions using LCM in different situations',
    'le_sow'      => "* Engage in an interactive demonstration of finding LCM by responding to guiding questions and completing steps.\n* Perform or solve LCM exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we find the LCM of denominators in given fractions?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.4 Fractions',
    'slo_cd'      => 'add fractions using LCM',
    'slo_sow'     => "Lesson 2:\na) Use the LCM to add proper fractions with different denominators.\nb) Work out addition problems involving more than two fractions.",
    'le_cd'       => 'add fractions using LCM',
    'le_sow'      => "* Engage in an interactive demonstration of adding fractions by responding to guiding questions and completing steps.\n* Perform or solve addition exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we add fractions with different denominators using LCM?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.4 Fractions',
    'slo_cd'      => 'subtract fractions using LCM in different situations',
    'slo_sow'     => "Lesson 3:\na) Use the LCM to subtract proper fractions with different denominators.\nb) Solve subtraction problems involving fractions in real-life contexts.",
    'le_cd'       => 'subtract fractions using LCM in different situations',
    'le_sow'      => "* Engage in an interactive demonstration of subtracting fractions by responding to guiding questions and completing steps.\n* Perform or solve subtraction exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we subtract fractions with different denominators using LCM?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.4 Fractions',
    'slo_cd'      => 'add mixed numbers in different situations',
    'slo_sow'     => "Lesson 4:\na) Convert mixed numbers into improper fractions for addition.\nb) Add mixed numbers by grouping whole numbers and fractions separately.",
    'le_cd'       => 'add mixed numbers in different situations',
    'le_sow'      => "* Engage in an interactive demonstration of adding mixed numbers by responding to guiding questions and completing steps.\n* Perform or solve addition exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we add mixed numbers accurately?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.4 Fractions',
    'slo_cd'      => 'subtract mixed numbers in different situations',
    'slo_sow'     => "Lesson 5:\na) Convert mixed numbers into improper fractions for subtraction.\nb) Subtract mixed numbers accurately using the regrouping/borrowing method.",
    'le_cd'       => 'subtract mixed numbers in different situations',
    'le_sow'      => "* Engage in an interactive demonstration of subtracting mixed numbers by responding to guiding questions and completing steps.\n* Perform or solve subtraction exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we subtract mixed numbers accurately?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.4 Fractions',
    'slo_cd'      => 'identify reciprocal of proper fractions up to a 2-digit number',
    'slo_sow'     => "Lesson 6:\na) Define the term \"reciprocal\" of a fraction.\nb) Identify and write the reciprocals of proper fractions up to a 2-digit number.",
    'le_cd'       => 'identify reciprocal of proper fractions up to a 2-digit number',
    'le_sow'      => "* Engage in an interactive demonstration of reciprocals by responding to guiding questions and completing steps.\n* Perform or solve reciprocal exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'What is a reciprocal and how do we find it for a proper fraction?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.4 Fractions',
    'slo_cd'      => 'work out squares of fractions',
    'slo_sow'     => "Lesson 7:\na) Multiply a fraction by itself to find its square.\nb) Calculate squares of fractions with a 1-digit numerator and 2-digit denominator.",
    'le_cd'       => 'work out squares of fractions',
    'le_sow'      => "* Engage in an interactive demonstration of squares of fractions by responding to guiding questions and completing steps.\n* Perform or solve square exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we calculate the square of a fraction?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.4 Fractions',
    'slo_cd'      => 'express a fraction as a percentage',
    'slo_sow'     => "Lesson 8:\na) Define percentage as a fraction with a denominator of 100.\nb) Convert given fractions into percentages accurately.",
    'le_cd'       => 'express a fraction as a percentage',
    'le_sow'      => "* Engage in an interactive demonstration of converting fractions to percentages by responding to guiding questions and completing steps.\n* Perform or solve conversion exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we express a fraction as a percentage?',
  ],

  // ═══════════════════════════════════════════════════════════════════════
  // 1.0 NUMBERS — 1.5 Decimals (10 Lessons)
  // ═══════════════════════════════════════════════════════════════════════
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.5 Decimals',
    'slo_cd'      => 'identify decimals up to ten thousandths in different situations',
    'slo_sow'     => "Lesson 1:\na) Identify the place value of digits in decimals up to ten thousandths.\nb) Read and write decimal numbers up to four decimal places correctly.",
    'le_cd'       => 'identify decimals up to ten thousandths in different situations',
    'le_sow'      => "* Engage in an interactive demonstration of decimal place value by responding to guiding questions and completing steps on the chalkboard.\n* Perform or solve place value exercises in exercise books individually using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we identify the place value of digits in decimals up to ten thousandths?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.5 Decimals',
    'slo_cd'      => 'round off decimals up to 3 decimal places in different situations',
    'slo_sow'     => "Lesson 2:\na) Identify the rounding digit for the first decimal place (tenths).\nb) Round off given decimals to 1 decimal place accurately.",
    'le_cd'       => 'round off decimals up to 3 decimal places in different situations',
    'le_sow'      => "* Engage in an interactive demonstration of rounding decimals by responding to guiding questions and completing steps.\n* Perform or solve rounding exercises in exercise books individually using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we round off decimals to 1 decimal place?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.5 Decimals',
    'slo_cd'      => 'round off decimals up to 3 decimal places',
    'slo_sow'     => "Lesson 3:\na) Identify the rounding digit for the second decimal place (hundredths).\nb) Round off given decimals to 2 decimal places accurately.",
    'le_cd'       => 'round off decimals up to 3 decimal places',
    'le_sow'      => "* Engage in an interactive demonstration of rounding to two decimal places by responding to guiding questions and completing steps.\n* Perform or solve rounding exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we round off decimals to 2 decimal places?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.5 Decimals',
    'slo_cd'      => 'round off decimals up to 3 decimal places',
    'slo_sow'     => "Lesson 4:\na) Identify the rounding digit for the third decimal place (thousandths).\nb) Round off given decimals to 3 decimal places accurately.",
    'le_cd'       => 'round off decimals up to 3 decimal places',
    'le_sow'      => "* Engage in an interactive demonstration of rounding to three decimal places by responding to guiding questions and completing steps.\n* Perform or solve rounding exercises in exercise books individually using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we round off decimals to 3 decimal places?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.5 Decimals',
    'slo_cd'      => 'convert decimals to fractions and fractions to decimals in different situations',
    'slo_sow'     => "Lesson 5:\na) Identify the relationship between decimal places and denominators (10, 100, 1000, 10000).\nb) Convert given decimals into fractions in their simplest form.",
    'le_cd'       => 'convert decimals to fractions and fractions to decimals in different situations',
    'le_sow'      => "* Engage in an interactive demonstration of decimal-to-fraction conversion by responding to guiding questions and completing steps.\n* Perform or solve conversion exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we convert decimals into fractions in their simplest form?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.5 Decimals',
    'slo_cd'      => 'convert decimals to fractions and fractions to decimals',
    'slo_sow'     => "Lesson 6:\na) Use division of the numerator by the denominator to find a decimal.\nb) Convert proper fractions into decimals accurately.",
    'le_cd'       => 'convert decimals to fractions and fractions to decimals',
    'le_sow'      => "* Engage in an interactive demonstration of fraction-to-decimal conversion by responding to guiding questions and completing steps.\n* Perform or solve conversion exercises in exercise books individually using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we convert proper fractions into decimals?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.5 Decimals',
    'slo_cd'      => 'convert decimals to percentages and percentages to decimals in different situations',
    'slo_sow'     => "Lesson 7:\na) Multiply a decimal by 100 to determine its percentage value.\nb) Convert given decimals into percentages correctly.",
    'le_cd'       => 'convert decimals to percentages and percentages to decimals in different situations',
    'le_sow'      => "* Engage in an interactive demonstration of decimal-to-percentage conversion by responding to guiding questions and completing steps.\n* Perform or solve conversion exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we convert decimals into percentages?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.5 Decimals',
    'slo_cd'      => 'convert decimals to percentages and percentages to decimals',
    'slo_sow'     => "Lesson 8:\na) Divide a percentage by 100 to determine its decimal value.\nb) Convert given percentages into decimals accurately.",
    'le_cd'       => 'convert decimals to percentages and percentages to decimals',
    'le_sow'      => "* Engage in an interactive demonstration of percentage-to-decimal conversion by responding to guiding questions and completing steps.\n* Perform or solve conversion exercises in exercise books individually using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we convert percentages into decimals?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.5 Decimals',
    'slo_cd'      => 'add decimals up to 4-decimal places in different situations',
    'slo_sow'     => "Lesson 9:\na) Align decimal points vertically for multi-digit addition.\nb) Add decimals up to 4-decimal places accurately.",
    'le_cd'       => 'add decimals up to 4-decimal places in different situations',
    'le_sow'      => "* Engage in an interactive demonstration of decimal addition by responding to guiding questions and completing steps.\n* Perform or solve addition exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we add decimals up to 4 decimal places?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.5 Decimals',
    'slo_cd'      => 'subtract decimals up to 4-decimal places in different situations',
    'slo_sow'     => "Lesson 10:\na) Align decimal points vertically for multi-digit subtraction.\nb) Subtract decimals up to 4-decimal places accurately.",
    'le_cd'       => 'subtract decimals up to 4-decimal places in different situations',
    'le_sow'      => "* Engage in an interactive demonstration of decimal subtraction by responding to guiding questions and completing steps.\n* Perform or solve subtraction exercises in exercise books individually using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we subtract decimals up to 4 decimal places?',
  ],

  // ═══════════════════════════════════════════════════════════════════════
  // 1.0 NUMBERS — 1.6 Inequalities (4 Lessons)
  // ═══════════════════════════════════════════════════════════════════════
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.6 Inequalities',
    'slo_cd'      => 'form simple inequalities in one unknown involving real-life situations',
    'slo_sow'     => "Lesson 1:\na) Identify the inequality symbols (>, <, >=, <=).\nb) Form simple inequalities in one unknown from given word statements.",
    'le_cd'       => 'form simple inequalities in one unknown involving real-life situations',
    'le_sow'      => "* Engage in an interactive demonstration of forming inequalities by responding to guiding questions and completing steps using the chalkboard or tablet.\n* Perform or solve exercises in exercise books individually using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we form simple inequalities from real-life situations?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.6 Inequalities',
    'slo_cd'      => 'simplify inequalities in one unknown involving real-life situations',
    'slo_sow'     => "Lesson 2:\na) Group like terms in a simple inequality expression.\nb) Simplify inequalities in one unknown by adding or subtracting terms.",
    'le_cd'       => 'simplify inequalities in one unknown involving real-life situations',
    'le_sow'      => "* Engage in an interactive demonstration of simplifying inequalities by responding to guiding questions and completing steps.\n* Perform or solve simplification exercises individually or in groups in exercise books.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we simplify inequalities in one unknown?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.6 Inequalities',
    'slo_cd'      => 'solve simple inequalities in one unknown involving real-life situations',
    'slo_sow'     => "Lesson 3:\na) Apply inverse operations to solve for an unknown in an inequality.\nb) Solve simple inequalities involving one step of calculation.",
    'le_cd'       => 'solve simple inequalities in one unknown involving real-life situations',
    'le_sow'      => "* Engage in an interactive demonstration of solving inequalities by responding to guiding questions and completing steps.\n* Perform or solve solving exercises individually in exercise books following demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we solve simple inequalities in one unknown?',
  ],
  [
    'strand'      => '1.0 Numbers',
    'sub_strand'  => '1.6 Inequalities',
    'slo_cd'      => 'appreciate use of inequalities in real-life situations',
    'slo_sow'     => "Lesson 4:\na) Solve real-life problems involving inequalities (e.g., age or price limits).\nb) Evaluate proficiency in inequalities through a summary exercise.",
    'le_cd'       => 'appreciate use of inequalities in real-life situations',
    'le_sow'      => "* Engage in an interactive review of inequalities by responding to guiding questions and completing steps.\n* Perform or solve a summary assessment exercise individually in exercise books.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we apply inequalities to solve real-life problems?',
  ],

  // ═══════════════════════════════════════════════════════════════════════
  // 2.0 MEASUREMENT — 2.1 Length (12 Lessons)
  // ═══════════════════════════════════════════════════════════════════════
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.1 Length',
    'slo_cd'      => 'use the millimetre (mm) as a unit of measuring length in different situations',
    'slo_sow'     => "Lesson 1:\na) Identify the millimetre (mm) marks on a ruler or tape measure.\nb) Measure the length of small objects in millimetres accurately.",
    'le_cd'       => 'use the millimetre (mm) as a unit of measuring length in different situations',
    'le_sow'      => "* Engage in an interactive demonstration of measuring in mm by responding to guiding questions and completing steps.\n* Perform or solve measurement exercises individually using textbooks and rulers.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we measure the length of small objects in millimetres?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.1 Length',
    'slo_cd'      => 'establish the relationship between the millimetre and centimetre',
    'slo_sow'     => "Lesson 2:\na) State the number of millimetres in one centimetre (1 cm = 10 mm).\nb) Compare lengths given in mm and cm.",
    'le_cd'       => 'establish the relationship between the millimetre and centimetre',
    'le_sow'      => "* Engage in an interactive demonstration of the relationship between mm and cm by responding to guiding questions.\n* Perform or solve comparison exercises in exercise books using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'What is the relationship between millimetres and centimetres?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.1 Length',
    'slo_cd'      => 'convert centimetres and millimetres to millimetres',
    'slo_sow'     => "Lesson 3:\na) Convert measurements from centimetres into millimetres.\nb) Convert measurements from millimetres into centimetres.",
    'le_cd'       => 'convert centimetres and millimetres to millimetres',
    'le_sow'      => "* Engage in an interactive demonstration of length conversion by responding to guiding questions and completing steps.\n* Perform or solve conversion exercises individually in exercise books.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we convert between centimetres and millimetres?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.1 Length',
    'slo_cd'      => 'add centimetres and millimetres in different situations',
    'slo_sow'     => "Lesson 4:\na) Align measurements in cm and mm for addition.\nb) Add lengths involving centimetres and millimetres accurately.",
    'le_cd'       => 'add centimetres and millimetres in different situations',
    'le_sow'      => "* Engage in an interactive demonstration of adding lengths by responding to guiding questions and completing steps.\n* Perform or solve addition exercises in groups or individually.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we add lengths in centimetres and millimetres?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.1 Length',
    'slo_cd'      => 'subtract centimetres and millimetres in different situations',
    'slo_sow'     => "Lesson 5:\na) Align measurements in cm and mm for subtraction.\nb) Subtract lengths involving centimetres and millimetres including regrouping.",
    'le_cd'       => 'subtract centimetres and millimetres in different situations',
    'le_sow'      => "* Engage in an interactive demonstration of subtracting lengths by responding to guiding questions and completing steps.\n* Perform or solve subtraction exercises individually in exercise books.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we subtract lengths in centimetres and millimetres?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.1 Length',
    'slo_cd'      => 'multiply centimetres and millimetres by whole numbers',
    'slo_sow'     => "Lesson 6:\na) Multiply lengths in cm and mm by a single-digit whole number.\nb) Express the product in both cm and mm where necessary.",
    'le_cd'       => 'multiply centimetres and millimetres by whole numbers',
    'le_sow'      => "* Engage in an interactive demonstration of multiplying lengths by responding to guiding questions and completing steps.\n* Perform or solve multiplication exercises individually or in groups.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we multiply lengths in cm and mm by whole numbers?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.1 Length',
    'slo_cd'      => 'divide centimetres and millimetres by whole numbers',
    'slo_sow'     => "Lesson 7:\na) Divide lengths in cm and mm by a single-digit whole number.\nb) Apply division of length to solve real-life sharing problems.",
    'le_cd'       => 'divide centimetres and millimetres by whole numbers',
    'le_sow'      => "* Engage in an interactive demonstration of dividing length by responding to guiding questions and completing steps.\n* Perform or solve division exercises in exercise books following demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we divide lengths in cm and mm by whole numbers?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.1 Length',
    'slo_cd'      => 'identify the relationship between circumference and diameter',
    'slo_sow'     => "Lesson 8:\na) Identify the circumference, diameter, and radius of a circle.\nb) Describe the relationship between the circumference and diameter of a circle.",
    'le_cd'       => 'identify the relationship between circumference and diameter',
    'le_sow'      => "* Engage in an interactive demonstration of circle parts by responding to guiding questions and completing steps.\n* Perform or solve identification exercises individually in exercise books.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'What are the parts of a circle and how are the circumference and diameter related?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.1 Length',
    'slo_cd'      => 'identify the relationship between circumference and diameter in different situations',
    'slo_sow'     => "Lesson 9:\na) Define the terms diameter and radius.\nb) Calculate the diameter of a circle given the radius and vice versa.",
    'le_cd'       => 'identify the relationship between circumference and diameter in different situations',
    'le_sow'      => "* Engage in an interactive demonstration of circle properties by responding to guiding questions and completing steps on the chalkboard.\n* Perform or solve diameter and radius exercises individually in exercise books using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we calculate the diameter of a circle given its radius?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.1 Length',
    'slo_cd'      => 'identify the relationship between circumference and diameter',
    'slo_sow'     => "Lesson 10:\na) Measure the circumference and diameter of various circular objects using a string or tape.\nb) Discover the constant relationship (pi) between circumference and diameter.",
    'le_cd'       => 'identify the relationship between circumference and diameter',
    'le_sow'      => "* Engage in an interactive demonstration of measuring circles by responding to guiding questions and completing steps.\n* Perform or solve relationship discovery exercises in groups using available circular objects.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'What is the constant relationship (pi) between circumference and diameter?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.1 Length',
    'slo_cd'      => 'appreciate use of length in real-life situations',
    'slo_sow'     => "Lesson 11:\na) Solve word problems involving the conversion of mm, cm, and m.\nb) Apply measurement skills to estimate lengths of school structures.",
    'le_cd'       => 'appreciate use of length in real-life situations',
    'le_sow'      => "* Engage in an interactive demonstration of real-life length applications by responding to guiding questions and completing steps.\n* Perform or solve word problems individually in exercise books using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we apply length measurement to solve real-life problems?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.1 Length',
    'slo_cd'      => 'appreciate use of length',
    'slo_sow'     => "Lesson 12:\na) Evaluate mastery of conversion, addition, and subtraction of length units.\nb) Review common errors in measuring small objects.",
    'le_cd'       => 'appreciate use of length',
    'le_sow'      => "* Engage in an interactive review of length concepts by responding to guiding questions and completing steps.\n* Perform or solve a summary assessment exercise individually in exercise books.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we demonstrate mastery of length measurement?',
  ],

  // ═══════════════════════════════════════════════════════════════════════
  // 2.0 MEASUREMENT — 2.2 Area (3 Lessons)
  // ═══════════════════════════════════════════════════════════════════════
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.2 Area',
    'slo_cd'      => 'work out area of triangles in square centimetres (cm2)',
    'slo_sow'     => "Lesson 1:\na) Identify the base and the height of a right-angled triangle.\nb) Calculate the area of a triangle using the formula A = 1/2 x base x height.",
    'le_cd'       => 'work out area of triangles in square centimetres (cm2)',
    'le_sow'      => "* Engage in an interactive demonstration of the area formula by responding to guiding questions and completing steps.\n* Perform or solve area exercises individually in exercise books using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we calculate the area of a triangle?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.2 Area',
    'slo_cd'      => 'work out area of combined shapes involving squares',
    'slo_sow'     => "Lesson 2:\na) Break down a combined shape into simple squares and rectangles.\nb) Calculate the total area of combined shapes accurately.",
    'le_cd'       => 'work out area of combined shapes involving squares',
    'le_sow'      => "* Engage in an interactive demonstration of dissecting shapes by responding to guiding questions and completing steps.\n* Perform or solve combined area exercises in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we calculate the area of combined shapes?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.2 Area',
    'slo_cd'      => 'estimate the area of circles by counting squares',
    'slo_sow'     => "Lesson 3:\na) Place circular objects on a square-grid paper.\nb) Estimate the area of the circle by counting full and half-squares.",
    'le_cd'       => 'estimate the area of circles by counting squares',
    'le_sow'      => "* Engage in an interactive demonstration of area estimation by responding to guiding questions and completing steps using the chalkboard grid.\n* Perform or solve estimation exercises individually in exercise books.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we estimate the area of a circle by counting squares?',
  ],

  // ═══════════════════════════════════════════════════════════════════════
  // 2.0 MEASUREMENT — 2.3 Capacity (6 Lessons)
  // ═══════════════════════════════════════════════════════════════════════
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.3 Capacity',
    'slo_cd'      => 'identify relationship among cubic centimetres (cm3), millilitres and litres',
    'slo_sow'     => "Lesson 1:\na) Define the unit cubic centimetre (cm3).\nb) Establish the relationship between cm3, millilitres, and litres.",
    'le_cd'       => 'identify relationship among cubic centimetres (cm3), millilitres and litres',
    'le_sow'      => "* Engage in an interactive demonstration of capacity units by responding to guiding questions and completing steps on the chalkboard.\n* Perform or solve unit relationship exercises in exercise books individually using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'What is the relationship among cm3, millilitres, and litres?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.3 Capacity',
    'slo_cd'      => 'identify relationship among cm3, ml and litres',
    'slo_sow'     => "Lesson 2:\na) Use the relationship 1 litre = 1000 cm3 in calculations.\nb) Calculate capacity in litres given values in cubic centimetres.",
    'le_cd'       => 'identify relationship among cm3, ml and litres',
    'le_sow'      => "* Engage in an interactive demonstration of unit conversion by responding to guiding questions and completing steps.\n* Perform or solve capacity exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we use the relationship 1 litre = 1000 cm3 in calculations?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.3 Capacity',
    'slo_cd'      => 'convert litres to millilitres in different situations',
    'slo_sow'     => "Lesson 3:\na) Identify the conversion factor between litres and millilitres.\nb) Convert given capacity from litres into millilitres accurately.",
    'le_cd'       => 'convert litres to millilitres in different situations',
    'le_sow'      => "* Engage in an interactive demonstration of converting litres to millilitres by responding to guiding questions and completing steps.\n* Perform or solve conversion exercises individually in exercise books.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we convert litres to millilitres?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.3 Capacity',
    'slo_cd'      => 'convert millilitres to litres in different situations',
    'slo_sow'     => "Lesson 4:\na) Identify the division factor needed to change millilitres to litres.\nb) Convert given capacity from millilitres into litres accurately.",
    'le_cd'       => 'convert millilitres to litres in different situations',
    'le_sow'      => "* Engage in an interactive demonstration of converting ml to litres by responding to guiding questions and completing steps.\n* Perform or solve conversion exercises in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we convert millilitres to litres?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.3 Capacity',
    'slo_cd'      => 'convert capacity between units',
    'slo_sow'     => "Lesson 5:\na) Convert cubic centimetres (cm3) to millilitres and vice versa.\nb) Solve simple capacity conversion problems involving cm3 and ml.",
    'le_cd'       => 'convert capacity between units',
    'le_sow'      => "* Engage in an interactive demonstration of the 1:1 relationship between cm3 and ml by responding to guiding questions and completing steps.\n* Perform or solve relationship exercises individually in exercise books.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we convert between cm3 and millilitres?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.3 Capacity',
    'slo_cd'      => 'appreciate use of cm3 and litres in real life',
    'slo_sow'     => "Lesson 6:\na) Solve real-life word problems involving capacity units (cm3, ml, and litres).\nb) Evaluate mastery of capacity through a summary exercise.",
    'le_cd'       => 'appreciate use of cm3 and litres in real life',
    'le_sow'      => "* Engage in an interactive review of capacity by responding to guiding questions and completing steps.\n* Perform or solve a summary assessment exercise individually in exercise books.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we solve real-life problems involving capacity?',
  ],

  // ═══════════════════════════════════════════════════════════════════════
  // 2.0 MEASUREMENT — 2.4 Mass (10 Lessons)
  // ═══════════════════════════════════════════════════════════════════════
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.4 Mass',
    'slo_cd'      => 'identify the tonne as a unit for measuring mass in real life',
    'slo_sow'     => "Lesson 1:\na) Identify the tonne (t) as a unit for measuring very heavy mass.\nb) Name various commodities measured in tonnes in the community.",
    'le_cd'       => 'identify the tonne as a unit for measuring mass in real life',
    'le_sow'      => "* Engage in an interactive demonstration of large mass units by responding to guiding questions and completing steps.\n* Perform or solve identification exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'What is a tonne and when is it used as a unit of mass?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.4 Mass',
    'slo_cd'      => 'identify the relationship between the kilogramme and the tonne',
    'slo_sow'     => "Lesson 2:\na) State the relationship between the kilogramme and the tonne (1 tonne = 1000 kg).\nb) Compare masses given in kilogrammes and tonnes.",
    'le_cd'       => 'identify the relationship between the kilogramme and the tonne',
    'le_sow'      => "* Engage in an interactive demonstration of the kg-to-tonne relationship by responding to guiding questions and completing steps.\n* Perform or solve relationship exercises in exercise books individually.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'What is the relationship between the kilogramme and the tonne?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.4 Mass',
    'slo_cd'      => 'estimate mass in tonnes in different situations',
    'slo_sow'     => "Lesson 3:\na) Estimate the mass of heavy items (e.g., a truckload of sand) in tonnes.\nb) Compare estimated mass with actual recorded mass of community items.",
    'le_cd'       => 'estimate mass in tonnes in different situations',
    'le_sow'      => "* Engage in an interactive demonstration of mass estimation by responding to guiding questions and completing steps using real-life examples.\n* Perform or solve estimation exercises in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we estimate the mass of heavy items in tonnes?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.4 Mass',
    'slo_cd'      => 'convert kilogrammes to tonnes and tonnes to kilogrammes',
    'slo_sow'     => "Lesson 4:\na) Use the conversion factor to change kilogrammes into tonnes.\nb) Convert given masses from kilogrammes to tonnes accurately.",
    'le_cd'       => 'convert kilogrammes to tonnes and tonnes to kilogrammes',
    'le_sow'      => "* Engage in an interactive demonstration of mass conversion by responding to guiding questions and completing steps on the chalkboard.\n* Perform or solve conversion exercises individually in exercise books using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we convert kilogrammes to tonnes?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.4 Mass',
    'slo_cd'      => 'convert tonnes to kilogrammes',
    'slo_sow'     => "Lesson 5:\na) Use the conversion factor to change tonnes into kilogrammes.\nb) Convert given masses from tonnes to kilogrammes accurately.",
    'le_cd'       => 'convert tonnes to kilogrammes',
    'le_sow'      => "* Engage in an interactive demonstration of mass conversion by responding to guiding questions and completing steps.\n* Perform or solve conversion exercises individually in exercise books using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we convert tonnes to kilogrammes?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.4 Mass',
    'slo_cd'      => 'add tonnes and kilogrammes in real-life situations',
    'slo_sow'     => "Lesson 6:\na) Align mass measurements in tonnes and kilogrammes for addition.\nb) Add masses involving tonnes and kilogrammes accurately.",
    'le_cd'       => 'add tonnes and kilogrammes in real-life situations',
    'le_sow'      => "* Engage in an interactive demonstration of adding mass by responding to guiding questions and completing steps.\n* Perform or solve addition exercises in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we add masses in tonnes and kilogrammes?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.4 Mass',
    'slo_cd'      => 'subtract tonnes and kilogrammes in real-life situations',
    'slo_sow'     => "Lesson 7:\na) Align mass measurements for subtraction including regrouping from tonnes to kg.\nb) Subtract masses involving tonnes and kilogrammes accurately.",
    'le_cd'       => 'subtract tonnes and kilogrammes in real-life situations',
    'le_sow'      => "* Engage in an interactive demonstration of subtracting mass by responding to guiding questions and completing steps.\n* Perform or solve subtraction exercises individually in exercise books using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we subtract masses in tonnes and kilogrammes?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.4 Mass',
    'slo_cd'      => 'multiply tonnes and kilogrammes by whole numbers',
    'slo_sow'     => "Lesson 8:\na) Multiply mass in tonnes and kg by a single-digit whole number.\nb) Carry out multiplication and convert kg to tonnes where necessary.",
    'le_cd'       => 'multiply tonnes and kilogrammes by whole numbers',
    'le_sow'      => "* Engage in an interactive demonstration of multiplying mass by responding to guiding questions and completing steps.\n* Perform or solve multiplication exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we multiply tonnes and kilogrammes by whole numbers?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.4 Mass',
    'slo_cd'      => 'divide tonnes and kilogrammes by whole numbers',
    'slo_sow'     => "Lesson 9:\na) Divide mass in tonnes and kg by a single-digit whole number.\nb) Apply division of mass to solve real-life distribution problems.",
    'le_cd'       => 'divide tonnes and kilogrammes by whole numbers',
    'le_sow'      => "* Engage in an interactive demonstration of dividing mass by responding to guiding questions and completing steps.\n* Perform or solve division exercises individually in exercise books using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we divide tonnes and kilogrammes by whole numbers?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.4 Mass',
    'slo_cd'      => 'appreciate use of the kilogramme and tonne',
    'slo_sow'     => "Lesson 10:\na) Solve word problems involving mass units (kg and tonnes).\nb) Evaluate mastery of mass through a summary assessment exercise.",
    'le_cd'       => 'appreciate use of the kilogramme and tonne',
    'le_sow'      => "* Engage in an interactive review of mass by responding to guiding questions and completing steps.\n* Perform or solve a summary assessment exercise individually in exercise books.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we solve real-life problems involving mass in kg and tonnes?',
  ],

  // ═══════════════════════════════════════════════════════════════════════
  // 2.0 MEASUREMENT — 2.5 Time (5 Lessons)
  // ═══════════════════════════════════════════════════════════════════════
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.5 Time',
    'slo_cd'      => 'identify and write time in a.m. and p.m.',
    'slo_sow'     => "Lesson 1:\na) Identify the meaning of a.m. and p.m. in relation to the day.\nb) Write time in a.m. and p.m. correctly from clock faces.",
    'le_cd'       => 'identify and write time in a.m. and p.m.',
    'le_sow'      => "* Engage in an interactive demonstration of a.m./p.m. by responding to guiding questions and completing steps using the tablet or chalkboard.\n* Perform or solve time writing exercises individually in exercise books.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we identify and write time in a.m. and p.m.?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.5 Time',
    'slo_cd'      => 'relate time in a.m. and p.m. to the 24h clock system',
    'slo_sow'     => "Lesson 2:\na) Describe the 24-hour clock system and its advantages.\nb) Relate a.m. and p.m. times to their corresponding 24-hour values.",
    'le_cd'       => 'relate time in a.m. and p.m. to the 24h clock system',
    'le_sow'      => "* Engage in an interactive demonstration of the 24-hour clock by responding to guiding questions and completing steps on the chalkboard.\n* Perform or solve time relationship exercises individually in exercise books using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we relate a.m. and p.m. times to the 24-hour clock?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.5 Time',
    'slo_cd'      => 'convert time from 12h to 24h and 24h to 12h system',
    'slo_sow'     => "Lesson 3:\na) Convert time from the 12-hour system to the 24-hour system.\nb) Convert time from the 24-hour system to the 12-hour system accurately.",
    'le_cd'       => 'convert time from 12h to 24h and 24h to 12h system',
    'le_sow'      => "* Engage in an interactive demonstration of time conversion by responding to guiding questions and completing steps.\n* Perform or solve conversion exercises individually or in groups using the tablet or textbook.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we convert between the 12-hour and 24-hour clock systems?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.5 Time',
    'slo_cd'      => 'interpret travel timetable in different situations',
    'slo_sow'     => "Lesson 4:\na) Identify the components of a travel timetable (departure/arrival times).\nb) Interpret travel information from a given bus or train timetable.",
    'le_cd'       => 'interpret travel timetable in different situations',
    'le_sow'      => "* Engage in an interactive demonstration of reading timetables by responding to guiding questions and completing steps.\n* Perform or solve timetable interpretation exercises in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we interpret a travel timetable?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.5 Time',
    'slo_cd'      => 'appreciate use of time in both 12h and 24h systems',
    'slo_sow'     => "Lesson 5:\na) Calculate time durations between departure and arrival using travel timetables.\nb) Evaluate mastery of time concepts through a summary exercise.",
    'le_cd'       => 'appreciate use of time in both 12h and 24h systems',
    'le_sow'      => "* Engage in an interactive review of time by responding to guiding questions and completing steps.\n* Perform or solve a summary assessment exercise individually in exercise books.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we calculate time durations using travel timetables?',
  ],

  // ═══════════════════════════════════════════════════════════════════════
  // 2.0 MEASUREMENT — 2.6 Money (6 Lessons)
  // ═══════════════════════════════════════════════════════════════════════
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.6 Money',
    'slo_cd'      => 'prepare simple budget in different situations',
    'slo_sow'     => "Lesson 1:\na) Identify the components of a price list.\nb) Prepare a simple price list for items in the local community.",
    'le_cd'       => 'prepare simple budget in different situations',
    'le_sow'      => "* Engage in an interactive demonstration of price lists by responding to guiding questions and completing steps.\n* Perform or solve price list exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we prepare a price list for items in the community?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.6 Money',
    'slo_cd'      => 'prepare simple budget',
    'slo_sow'     => "Lesson 2:\na) Define a budget and its importance in financial planning.\nb) Prepare a simple budget for a given amount of money.",
    'le_cd'       => 'prepare simple budget',
    'le_sow'      => "* Engage in an interactive demonstration of budgeting by responding to guiding questions and completing steps using the tablet or chalkboard.\n* Perform or solve budgeting exercises individually in exercise books.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we prepare a simple budget?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.6 Money',
    'slo_cd'      => 'work out profit from sales',
    'slo_sow'     => "Lesson 3:\na) Define the terms \"buying price\" and \"selling price\".\nb) Calculate profit made from the sale of various items.",
    'le_cd'       => 'work out profit from sales',
    'le_sow'      => "* Engage in an interactive demonstration of profit calculation by responding to guiding questions and completing steps.\n* Perform or solve profit exercises individually or in groups using demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we calculate profit from the sale of items?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.6 Money',
    'slo_cd'      => 'calculate loss realised from sales',
    'slo_sow'     => "Lesson 4:\na) Identify situations where a loss is incurred in a transaction.\nb) Calculate the loss realised from the sale of different items.",
    'le_cd'       => 'calculate loss realised from sales',
    'le_sow'      => "* Engage in an interactive demonstration of loss calculation by responding to guiding questions and completing steps.\n* Perform or solve loss exercises individually in exercise books following demonstrated procedures.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we calculate a loss from the sale of items?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.6 Money',
    'slo_cd'      => 'identify types of taxes in different situations',
    'slo_sow'     => "Lesson 5:\na) Identify Income Tax and Value Added Tax (VAT).\nb) Discuss the importance of paying taxes for national development.",
    'le_cd'       => 'identify types of taxes in different situations',
    'le_sow'      => "* Engage in an interactive demonstration of tax types by responding to guiding questions and completing steps.\n* Perform or solve tax identification exercises individually or in groups.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'What are the types of taxes and why are they important?',
  ],
  [
    'strand'      => '2.0 Measurement',
    'sub_strand'  => '2.6 Money',
    'slo_cd'      => 'appreciate use of money in real-life situations',
    'slo_sow'     => "Lesson 6:\na) Solve real-life word problems involving profit, loss, and budgets.\nb) Evaluate proficiency in money concepts through a summary assessment.",
    'le_cd'       => 'appreciate use of money in real-life situations',
    'le_sow'      => "* Engage in an interactive review of money by responding to guiding questions and completing steps.\n* Perform or solve a summary assessment exercise individually in exercise books.\n* Receive assessment and feedback to improve understanding and performance.",
    'key_inquiry' => 'How do we apply money concepts to solve real-life problems?',
  ],

  // ═══════════════════════════════════════════════════════════════════════
  // 3.0 GEOMETRY — 3.1 Lines (6 Lessons)
  // ═══════════════════════════════════════════════════════════════════════
  [
    'strand'      => '3.0 Geometry',
    'sub_strand'  => '3.1 Lines',
    'slo_cd'      => 'identify types of lines in different situations',
    'slo_sow'     => "Lesson 1:\na) Identify and name different types of lines (straight, curved, horizontal, vertical).\nb) Draw examples of each type of line in the environment.",
    'le_cd'       => 'identify types of lines in different situations',
    'le_sow'      => "* Identify types of lines around the school compound and in the classroom.\n* Draw examples of each type of line in exercise books and share with the class.",
    'key_inquiry' => 'What are the different types of lines and where do we see them?',
  ],
  [
    'strand'      => '3.0 Geometry',
    'sub_strand'  => '3.1 Lines',
    'slo_cd'      => 'identify parallel lines in different situations',
    'slo_sow'     => "Lesson 2:\na) Define parallel lines.\nb) Identify parallel lines in shapes and the environment.",
    'le_cd'       => 'identify parallel lines in different situations',
    'le_sow'      => "* Identify parallel lines in classroom structures and objects.\n* Draw parallel lines freehand and share examples with the class.",
    'key_inquiry' => 'What are parallel lines and where do we find them?',
  ],
  [
    'strand'      => '3.0 Geometry',
    'sub_strand'  => '3.1 Lines',
    'slo_cd'      => 'identify perpendicular lines in different situations',
    'slo_sow'     => "Lesson 3:\na) Define perpendicular lines.\nb) Identify perpendicular lines in shapes and structures.",
    'le_cd'       => 'identify perpendicular lines in different situations',
    'le_sow'      => "* Identify perpendicular lines in corners of rooms and objects.\n* Draw perpendicular lines and verify using a set square.",
    'key_inquiry' => 'What are perpendicular lines and where do we find them?',
  ],
  [
    'strand'      => '3.0 Geometry',
    'sub_strand'  => '3.1 Lines',
    'slo_cd'      => 'draw parallel and perpendicular lines',
    'slo_sow'     => "Lesson 4:\na) Draw parallel lines using a ruler and set square.\nb) Draw perpendicular lines using a ruler and set square.",
    'le_cd'       => 'draw parallel and perpendicular lines',
    'le_sow'      => "* Draw parallel and perpendicular lines using a ruler and set square in exercise books.\n* Compare drawings with classmates and correct errors.",
    'key_inquiry' => 'How do we draw parallel and perpendicular lines accurately?',
  ],
  [
    'strand'      => '3.0 Geometry',
    'sub_strand'  => '3.1 Lines',
    'slo_cd'      => 'identify lines in 2-D shapes',
    'slo_sow'     => "Lesson 5:\na) Identify parallel and perpendicular lines in 2-D shapes.\nb) Count and categorise the types of lines in given shapes.",
    'le_cd'       => 'identify lines in 2-D shapes',
    'le_sow'      => "* Categorise lines in cut-out 2-D shapes as parallel or perpendicular.\n* Record findings in a table and share with the class.",
    'key_inquiry' => 'How do we identify parallel and perpendicular lines in 2-D shapes?',
  ],
  [
    'strand'      => '3.0 Geometry',
    'sub_strand'  => '3.1 Lines',
    'slo_cd'      => 'appreciate use of lines in real-life situations',
    'slo_sow'     => "Lesson 6:\na) Relate types of lines to real-life structures and patterns.\nb) Evaluate mastery of lines through a summary exercise.",
    'le_cd'       => 'appreciate use of lines in real-life situations',
    'le_sow'      => "* Identify uses of parallel and perpendicular lines in everyday structures.\n* Complete a summary exercise individually and receive feedback.",
    'key_inquiry' => 'How are lines used in real-life structures and patterns?',
  ],

  // ═══════════════════════════════════════════════════════════════════════
  // 3.0 GEOMETRY — 3.2 Angles (6 Lessons)
  // ═══════════════════════════════════════════════════════════════════════
  [
    'strand'      => '3.0 Geometry',
    'sub_strand'  => '3.2 Angles',
    'slo_cd'      => 'identify types of angles in different situations',
    'slo_sow'     => "Lesson 1:\na) Define an angle and identify its parts (arms and vertex).\nb) Classify angles as acute, right, obtuse, straight, or reflex.",
    'le_cd'       => 'identify types of angles in different situations',
    'le_sow'      => "* Identify different types of angles in the classroom environment.\n* Draw and label examples of each type of angle in exercise books.",
    'key_inquiry' => 'What are the types of angles and how do we identify them?',
  ],
  [
    'strand'      => '3.0 Geometry',
    'sub_strand'  => '3.2 Angles',
    'slo_cd'      => 'measure angles using a protractor',
    'slo_sow'     => "Lesson 2:\na) Identify the parts of a protractor.\nb) Measure given angles using a protractor accurately.",
    'le_cd'       => 'measure angles using a protractor',
    'le_sow'      => "* Practise reading the protractor scale in pairs.\n* Measure angles drawn on worksheets and record measurements in exercise books.",
    'key_inquiry' => 'How do we measure angles using a protractor?',
  ],
  [
    'strand'      => '3.0 Geometry',
    'sub_strand'  => '3.2 Angles',
    'slo_cd'      => 'construct angles using a protractor',
    'slo_sow'     => "Lesson 3:\na) Draw a baseline and mark a vertex.\nb) Construct angles of given sizes using a protractor.",
    'le_cd'       => 'construct angles using a protractor',
    'le_sow'      => "* Construct angles of specified degrees in exercise books.\n* Compare constructed angles with a partner and correct errors.",
    'key_inquiry' => 'How do we construct angles of given sizes using a protractor?',
  ],
  [
    'strand'      => '3.0 Geometry',
    'sub_strand'  => '3.2 Angles',
    'slo_cd'      => 'identify angles in shapes',
    'slo_sow'     => "Lesson 4:\na) Identify and name angles in triangles and quadrilaterals.\nb) Measure the angles of given polygons and record the results.",
    'le_cd'       => 'identify angles in shapes',
    'le_sow'      => "* Measure all interior angles of drawn triangles and quadrilaterals.\n* Record sums of angles and discuss findings in groups.",
    'key_inquiry' => 'How do we identify and measure angles in 2-D shapes?',
  ],
  [
    'strand'      => '3.0 Geometry',
    'sub_strand'  => '3.2 Angles',
    'slo_cd'      => 'apply knowledge of angles in real-life situations',
    'slo_sow'     => "Lesson 5:\na) Identify angles in real-life structures (rooftops, doors, road intersections).\nb) Estimate and verify the size of angles in familiar objects.",
    'le_cd'       => 'apply knowledge of angles in real-life situations',
    'le_sow'      => "* Search for angles in the school environment and estimate their sizes.\n* Record and verify estimates using a protractor.",
    'key_inquiry' => 'How do we find and estimate angles in real-life situations?',
  ],
  [
    'strand'      => '3.0 Geometry',
    'sub_strand'  => '3.2 Angles',
    'slo_cd'      => 'appreciate use of angles in real-life situations',
    'slo_sow'     => "Lesson 6:\na) Solve problems involving classification and measurement of angles.\nb) Evaluate mastery of angles through a summary exercise.",
    'le_cd'       => 'appreciate use of angles in real-life situations',
    'le_sow'      => "* Complete a summary exercise classifying and measuring angles.\n* Receive feedback and correct any misconceptions.",
    'key_inquiry' => 'How do we apply knowledge of angles to solve problems?',
  ],

  // ═══════════════════════════════════════════════════════════════════════
  // 3.0 GEOMETRY — 3.3 3-D Objects (6 Lessons)
  // ═══════════════════════════════════════════════════════════════════════
  [
    'strand'      => '3.0 Geometry',
    'sub_strand'  => '3.3 3-D Objects',
    'slo_cd'      => 'identify 3-D objects in different situations',
    'slo_sow'     => "Lesson 1:\na) Identify and name common 3-D objects (cube, cuboid, cylinder, sphere, cone, pyramid).\nb) Relate 3-D objects to real-life examples.",
    'le_cd'       => 'identify 3-D objects in different situations',
    'le_sow'      => "* Collect 3-D objects from the environment and name them.\n* Match objects to their geometric names and share with the class.",
    'key_inquiry' => 'What are 3-D objects and where do we find them in real life?',
  ],
  [
    'strand'      => '3.0 Geometry',
    'sub_strand'  => '3.3 3-D Objects',
    'slo_cd'      => 'describe properties of 3-D objects',
    'slo_sow'     => "Lesson 2:\na) Count and record the number of faces, edges, and vertices of given 3-D objects.\nb) Compare properties of different 3-D objects.",
    'le_cd'       => 'describe properties of 3-D objects',
    'le_sow'      => "* Handle 3-D objects and count faces, edges, and vertices.\n* Record findings in a table and compare with other groups.",
    'key_inquiry' => 'What are the properties (faces, edges, vertices) of 3-D objects?',
  ],
  [
    'strand'      => '3.0 Geometry',
    'sub_strand'  => '3.3 3-D Objects',
    'slo_cd'      => 'classify 3-D objects',
    'slo_sow'     => "Lesson 3:\na) Group 3-D objects by shared properties (flat or curved faces).\nb) Justify the classification of each object.",
    'le_cd'       => 'classify 3-D objects',
    'le_sow'      => "* Sort a collection of 3-D objects by their properties.\n* Present and justify classifications to the class.",
    'key_inquiry' => 'How do we classify 3-D objects by their properties?',
  ],
  [
    'strand'      => '3.0 Geometry',
    'sub_strand'  => '3.3 3-D Objects',
    'slo_cd'      => 'draw nets of 3-D objects',
    'slo_sow'     => "Lesson 4:\na) Identify the net of a cube and a cuboid.\nb) Cut out and fold nets to form 3-D objects.",
    'le_cd'       => 'draw nets of 3-D objects',
    'le_sow'      => "* Draw, cut, and fold nets to make cubes and cuboids.\n* Verify that the folded net forms the correct 3-D shape.",
    'key_inquiry' => 'How do we draw and use nets to form 3-D objects?',
  ],
  [
    'strand'      => '3.0 Geometry',
    'sub_strand'  => '3.3 3-D Objects',
    'slo_cd'      => 'relate 3-D objects to real-life items',
    'slo_sow'     => "Lesson 5:\na) Name everyday items that correspond to specific 3-D shapes.\nb) Sketch the 3-D shape for a given real-life object.",
    'le_cd'       => 'relate 3-D objects to real-life items',
    'le_sow'      => "* Match photographs of real objects to their 3-D geometric forms.\n* Sketch and label the geometric shapes found in everyday items.",
    'key_inquiry' => 'How do 3-D shapes appear in everyday objects?',
  ],
  [
    'strand'      => '3.0 Geometry',
    'sub_strand'  => '3.3 3-D Objects',
    'slo_cd'      => 'appreciate use of 3-D objects in real-life situations',
    'slo_sow'     => "Lesson 6:\na) Solve problems involving properties and nets of 3-D objects.\nb) Evaluate mastery of 3-D objects through a summary exercise.",
    'le_cd'       => 'appreciate use of 3-D objects in real-life situations',
    'le_sow'      => "* Complete a summary exercise on 3-D object properties and nets.\n* Receive feedback and review common errors.",
    'key_inquiry' => 'How do we apply knowledge of 3-D objects to solve problems?',
  ],

  // ═══════════════════════════════════════════════════════════════════════
  // 4.0 DATA HANDLING — 4.1 Bar Graphs (10 Lessons)
  // ═══════════════════════════════════════════════════════════════════════
  [
    'strand'      => '4.0 Data Handling',
    'sub_strand'  => '4.1 Bar Graphs',
    'slo_cd'      => 'collect and record data using tally marks',
    'slo_sow'     => "Lesson 1:\na) Define data and give examples of data collected in daily life.\nb) Use tally marks to record data from a survey.",
    'le_cd'       => 'collect and record data using tally marks',
    'le_sow'      => "* Conduct a simple class survey and record responses using tally marks.\n* Share tally results with the class and discuss.",
    'key_inquiry' => 'How do we collect and record data using tally marks?',
  ],
  [
    'strand'      => '4.0 Data Handling',
    'sub_strand'  => '4.1 Bar Graphs',
    'slo_cd'      => 'organise data in a frequency table',
    'slo_sow'     => "Lesson 2:\na) Transfer tally marks into a frequency table.\nb) Identify the highest and lowest frequencies in the table.",
    'le_cd'       => 'organise data in a frequency table',
    'le_sow'      => "* Convert tally data from Lesson 1 into a frequency table.\n* Identify and discuss the highest and lowest values.",
    'key_inquiry' => 'How do we organise data in a frequency table?',
  ],
  [
    'strand'      => '4.0 Data Handling',
    'sub_strand'  => '4.1 Bar Graphs',
    'slo_cd'      => 'identify the components of a bar graph',
    'slo_sow'     => "Lesson 3:\na) Identify the title, axes, labels, and scale of a bar graph.\nb) Describe the purpose of each component of a bar graph.",
    'le_cd'       => 'identify the components of a bar graph',
    'le_sow'      => "* Label the parts of a given bar graph using provided terms.\n* Discuss why each component is important for reading the graph.",
    'key_inquiry' => 'What are the components of a bar graph and what is the purpose of each?',
  ],
  [
    'strand'      => '4.0 Data Handling',
    'sub_strand'  => '4.1 Bar Graphs',
    'slo_cd'      => 'draw a vertical bar graph from collected data',
    'slo_sow'     => "Lesson 4:\na) Choose an appropriate scale for a vertical bar graph.\nb) Draw and label a vertical bar graph from a frequency table.",
    'le_cd'       => 'draw a vertical bar graph from collected data',
    'le_sow'      => "* Draw a vertical bar graph from the class survey frequency table.\n* Ensure title, labels, and scale are included.",
    'key_inquiry' => 'How do we draw a vertical bar graph from collected data?',
  ],
  [
    'strand'      => '4.0 Data Handling',
    'sub_strand'  => '4.1 Bar Graphs',
    'slo_cd'      => 'draw a horizontal bar graph from collected data',
    'slo_sow'     => "Lesson 5:\na) Choose an appropriate scale for a horizontal bar graph.\nb) Draw and label a horizontal bar graph from a frequency table.",
    'le_cd'       => 'draw a horizontal bar graph from collected data',
    'le_sow'      => "* Draw a horizontal bar graph from a provided frequency table.\n* Compare the horizontal and vertical formats and discuss.",
    'key_inquiry' => 'How do we draw a horizontal bar graph from collected data?',
  ],
  [
    'strand'      => '4.0 Data Handling',
    'sub_strand'  => '4.1 Bar Graphs',
    'slo_cd'      => 'read and interpret a bar graph',
    'slo_sow'     => "Lesson 6:\na) Read values from bars on a given bar graph.\nb) Answer questions based on information shown in a bar graph.",
    'le_cd'       => 'read and interpret a bar graph',
    'le_sow'      => "* Read and answer questions from a given bar graph in exercise books.\n* Discuss answers as a class and correct errors.",
    'key_inquiry' => 'How do we read and interpret a bar graph?',
  ],
  [
    'strand'      => '4.0 Data Handling',
    'sub_strand'  => '4.1 Bar Graphs',
    'slo_cd'      => 'compare information using bar graphs',
    'slo_sow'     => "Lesson 7:\na) Compare two sets of data displayed on the same bar graph.\nb) Draw conclusions from comparisons made on the bar graph.",
    'le_cd'       => 'compare information using bar graphs',
    'le_sow'      => "* Compare two bar graphs showing similar categories and discuss differences.\n* Write two conclusions from the comparison in exercise books.",
    'key_inquiry' => 'How do we compare information using bar graphs?',
  ],
  [
    'strand'      => '4.0 Data Handling',
    'sub_strand'  => '4.1 Bar Graphs',
    'slo_cd'      => 'answer questions from a bar graph',
    'slo_sow'     => "Lesson 8:\na) Identify the category with the highest and lowest values.\nb) Calculate the difference between values shown in a bar graph.",
    'le_cd'       => 'answer questions from a bar graph',
    'le_sow'      => "* Answer a set of comprehension questions from a bar graph individually.\n* Check answers in pairs and discuss any discrepancies.",
    'key_inquiry' => 'How do we answer questions using information from a bar graph?',
  ],
  [
    'strand'      => '4.0 Data Handling',
    'sub_strand'  => '4.1 Bar Graphs',
    'slo_cd'      => 'draw bar graphs from word problems',
    'slo_sow'     => "Lesson 9:\na) Extract data from a word problem to create a frequency table.\nb) Draw a bar graph from the extracted data.",
    'le_cd'       => 'draw bar graphs from word problems',
    'le_sow'      => "* Extract data from a provided word problem and draw the bar graph.\n* Compare bar graphs drawn by different groups and discuss.",
    'key_inquiry' => 'How do we draw a bar graph from information given in a word problem?',
  ],
  [
    'strand'      => '4.0 Data Handling',
    'sub_strand'  => '4.1 Bar Graphs',
    'slo_cd'      => 'appreciate use of bar graphs in real-life situations',
    'slo_sow'     => "Lesson 10:\na) Solve real-life problems by drawing and reading bar graphs.\nb) Evaluate mastery of bar graphs through a summary exercise.",
    'le_cd'       => 'appreciate use of bar graphs in real-life situations',
    'le_sow'      => "* Complete a summary task: draw, label, and answer questions from a bar graph.\n* Receive feedback and review errors.",
    'key_inquiry' => 'How do we use bar graphs to represent and interpret real-life data?',
  ],
];

// ── Insert ───────────────────────────────────────────────────────────────────
$sql = 'INSERT INTO scheme_of_work
            (learning_area_id, week, lesson, strand, sub_strand,
             slo_cd, slo_sow, le_cd, le_sow, key_inquiry, resources, assessment, remarks)
        VALUES
            (:laid, :week, :lesson, :strand, :sub_strand,
             :slo_cd, :slo_sow, :le_cd, :le_sow, :key_inquiry, \'\', \'\', \'\')';
$stmt = $pdo->prepare($sql);

$currentWeek  = 1;
$lessonInWeek = 1;
$inserted     = 0;

foreach ($lessons as $L) {
    $stmt->execute([
        ':laid'        => $learningAreaId,
        ':week'        => $currentWeek,
        ':lesson'      => $lessonInWeek,
        ':strand'      => $L['strand'],
        ':sub_strand'  => $L['sub_strand'],
        ':slo_cd'      => $L['slo_cd'],
        ':slo_sow'     => $L['slo_sow'],
        ':le_cd'       => $L['le_cd'],
        ':le_sow'      => $L['le_sow'],
        ':key_inquiry' => $L['key_inquiry'],
    ]);
    $inserted++;
    $lessonInWeek++;
    if ($lessonInWeek > $LESSONS_PER_WEEK) {
        $lessonInWeek = 1;
        $currentWeek++;
    }
}

$totalWeeks = $currentWeek - ($lessonInWeek === 1 ? 1 : 0);
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<title>Grade 6 Maths Seed Complete</title>
<style>
  body{font-family:Segoe UI,sans-serif;padding:30px;background:#f3f4f6}
  .card{background:#fff;border-radius:8px;padding:28px;max-width:720px;box-shadow:0 1px 4px rgba(0,0,0,.12)}
  h2{color:#1a56db;margin-bottom:8px}
  table{width:100%;border-collapse:collapse;margin-top:18px}
  th{background:#1a56db;color:#fff;padding:9px 12px;text-align:left}
  td{padding:8px 12px;border-bottom:1px solid #e5e7eb;font-size:13px}
  tr:last-child td{border-bottom:none}
  .btn{display:inline-block;margin-top:20px;background:#1a56db;color:#fff;padding:10px 22px;border-radius:6px;text-decoration:none;font-weight:600}
</style></head><body>
<div class="card">
  <h2>&#10003; Grade 6 Mathematics &mdash; Seeded Successfully</h2>
  <p>Learning Area: <strong><?= htmlspecialchars($la['name']) ?></strong> (<?= htmlspecialchars($la['grade_name']) ?>)</p>
  <p><strong><?= $inserted ?> lessons</strong> inserted across <strong><?= $totalWeeks ?> weeks</strong> at <?= $LESSONS_PER_WEEK ?> lessons/week.</p>

  <table>
    <tr><th>Strand</th><th>Sub-Strand</th><th>Lessons</th></tr>
    <tr><td>1.0 Numbers</td><td>1.1 Whole Numbers</td><td>10</td></tr>
    <tr><td>1.0 Numbers</td><td>1.2 Multiplication</td><td>4</td></tr>
    <tr><td>1.0 Numbers</td><td>1.3 Division</td><td>6</td></tr>
    <tr><td>1.0 Numbers</td><td>1.4 Fractions</td><td>8</td></tr>
    <tr><td>1.0 Numbers</td><td>1.5 Decimals</td><td>10</td></tr>
    <tr><td>1.0 Numbers</td><td>1.6 Inequalities</td><td>4</td></tr>
    <tr><td>2.0 Measurement</td><td>2.1 Length</td><td>12</td></tr>
    <tr><td>2.0 Measurement</td><td>2.2 Area</td><td>3</td></tr>
    <tr><td>2.0 Measurement</td><td>2.3 Capacity</td><td>6</td></tr>
    <tr><td>2.0 Measurement</td><td>2.4 Mass</td><td>10</td></tr>
    <tr><td>2.0 Measurement</td><td>2.5 Time</td><td>5</td></tr>
    <tr><td>2.0 Measurement</td><td>2.6 Money</td><td>6</td></tr>
    <tr><td>3.0 Geometry</td><td>3.1 Lines</td><td>6</td></tr>
    <tr><td>3.0 Geometry</td><td>3.2 Angles</td><td>6</td></tr>
    <tr><td>3.0 Geometry</td><td>3.3 3-D Objects</td><td>6</td></tr>
    <tr><td>4.0 Data Handling</td><td>4.1 Bar Graphs</td><td>10</td></tr>
    <tr><td colspan="2"><strong>Total</strong></td><td><strong><?= $inserted ?></strong></td></tr>
  </table>

  <a class="btn" href="index.php?learning_area_id=<?= $learningAreaId ?>">Open Scheme of Work &rarr;</a>
  &nbsp;
  <a class="btn" style="background:#6b7280" href="curriculum.php">Back to Curriculum</a>
</div>
</body></html>
  // ── 1.0 Numbers ─────────────────────────────────────────────────────────
  ['1.0 Numbers', '1.1 Whole Numbers',  20, [
    'What are whole numbers and how are they used in real life?',
    'How do we read and write large whole numbers?',
    'How do we compare and order whole numbers?',
    'How do we round off whole numbers?',
    'How do we add large whole numbers?',
    'How do we subtract large whole numbers?',
    'How do we solve problems involving addition and subtraction of whole numbers?',
    'How do we estimate sums and differences?',
    'How do we use number patterns in whole numbers?',
    'How do we apply whole numbers to solve real-life problems?',
    'How do we identify and use factors of whole numbers?',
    'How do we identify and use multiples of whole numbers?',
    'What are prime and composite numbers?',
    'How do we find the LCM of whole numbers?',
    'How do we find the GCD of whole numbers?',
    'How do we apply factors and multiples in daily life?',
    'How do we use the number line for whole numbers?',
    'How do we work with Roman numerals?',
    'How do we apply whole number operations in context?',
    'How do I demonstrate understanding of whole numbers?',
  ]],
  ['1.0 Numbers', '1.2 Multiplication', 6, [
    'What is multiplication and how is it related to addition?',
    'How do we multiply by 2-digit numbers?',
    'How do we multiply by 3-digit numbers?',
    'How do we use multiplication to solve word problems?',
    'How do we estimate products in multiplication?',
    'How do I demonstrate understanding of multiplication?',
  ]],
  ['1.0 Numbers', '1.3 Division', 6, [
    'What is division and how is it related to multiplication?',
    'How do we divide by 2-digit numbers?',
    'How do we divide with remainders?',
    'How do we use division to solve word problems?',
    'How do we estimate quotients in division?',
    'How do I demonstrate understanding of division?',
  ]],
  ['1.0 Numbers', '1.4 Fractions', 12, [
    'What are fractions and how are they represented?',
    'How do we identify and compare fractions?',
    'How do we convert between fractions and mixed numbers?',
    'How do we find equivalent fractions?',
    'How do we add fractions with same denominators?',
    'How do we add fractions with different denominators?',
    'How do we subtract fractions with same denominators?',
    'How do we subtract fractions with different denominators?',
    'How do we multiply a fraction by a whole number?',
    'How do we multiply fractions?',
    'How do we solve problems involving fractions?',
    'How do I demonstrate understanding of fractions?',
  ]],
  ['1.0 Numbers', '1.5 Decimals', 12, [
    'What are decimals and how are they related to fractions?',
    'How do we read and write decimal numbers?',
    'How do we compare and order decimals?',
    'How do we round off decimals?',
    'How do we add decimals?',
    'How do we subtract decimals?',
    'How do we multiply decimals?',
    'How do we divide decimals by a whole number?',
    'How do we convert between fractions and decimals?',
    'How do we use decimals in money calculations?',
    'How do we solve problems involving decimals?',
    'How do I demonstrate understanding of decimals?',
  ]],
  ['1.0 Numbers', '1.6 Inequalities', 8, [
    'What are inequalities and how are they shown?',
    'How do we use symbols <, >, = to compare numbers?',
    'How do we represent inequalities on a number line?',
    'How do we solve simple inequalities?',
    'How do we apply inequalities in real-life situations?',
    'How do we write inequality statements from word problems?',
    'How do we verify solutions to inequalities?',
    'How do I demonstrate understanding of inequalities?',
  ]],

  // ── 2.0 Measurement ─────────────────────────────────────────────────────
  ['2.0 Measurement', '2.1 Length', 14, [
    'What are the units of measuring length?',
    'How do we measure length using a ruler?',
    'How do we convert units of length (km, m, cm, mm)?',
    'How do we add lengths?',
    'How do we subtract lengths?',
    'How do we multiply lengths by a whole number?',
    'How do we divide lengths?',
    'How do we estimate lengths in daily life?',
    'How do we find the perimeter of regular shapes?',
    'How do we find the perimeter of irregular shapes?',
    'How do we find the perimeter of combined shapes?',
    'How do we solve problems involving perimeter?',
    'How do we apply length measurement in real-life contexts?',
    'How do I demonstrate understanding of length and perimeter?',
  ]],
  ['2.0 Measurement', '2.2 Area', 6, [
    'What is area and how is it measured?',
    'How do we calculate the area of a rectangle?',
    'How do we calculate the area of a square?',
    'How do we calculate the area of a triangle?',
    'How do we solve problems involving area?',
    'How do I demonstrate understanding of area?',
  ]],
  ['2.0 Measurement', '2.3 Capacity', 6, [
    'What is capacity and what units are used to measure it?',
    'How do we measure capacity using litres and millilitres?',
    'How do we convert between litres and millilitres?',
    'How do we add and subtract capacities?',
    'How do we solve real-life problems involving capacity?',
    'How do I demonstrate understanding of capacity?',
  ]],
  ['2.0 Measurement', '2.4 Mass', 14, [
    'What is mass and what units are used to measure it?',
    'How do we measure mass using a scale?',
    'How do we convert between kg and g?',
    'How do we convert between tonnes and kg?',
    'How do we add masses?',
    'How do we subtract masses?',
    'How do we multiply mass by a whole number?',
    'How do we divide mass?',
    'How do we estimate mass in everyday situations?',
    'How do we solve word problems involving mass?',
    'How do we read and interpret scales?',
    'How do we compare masses of different objects?',
    'How do we apply mass measurement in real-life contexts?',
    'How do I demonstrate understanding of mass?',
  ]],
  ['2.0 Measurement', '2.5 Time', 10, [
    'How do we read time on a clock (12-hour and 24-hour)?',
    'How do we convert between hours, minutes, and seconds?',
    'How do we add time?',
    'How do we subtract time?',
    'How do we calculate duration of activities?',
    'How do we interpret timetables?',
    'How do we use a calendar to find dates and durations?',
    'How do we convert between days, weeks, months, and years?',
    'How do we solve real-life problems involving time?',
    'How do I demonstrate understanding of time?',
  ]],
  ['2.0 Measurement', '2.6 Money', 8, [
    'What are the Kenyan currency denominations?',
    'How do we convert between shillings and cents?',
    'How do we add and subtract money?',
    'How do we multiply and divide money?',
    'How do we calculate profit and loss?',
    'How do we calculate discount?',
    'How do we solve real-life problems involving money?',
    'How do I demonstrate understanding of money?',
  ]],

  // ── 3.0 Geometry ────────────────────────────────────────────────────────
  ['3.0 Geometry', '3.1 Lines', 6, [
    'What are the different types of lines?',
    'How do we identify parallel and perpendicular lines?',
    'How do we draw parallel lines?',
    'How do we draw perpendicular lines?',
    'How do we identify lines in 2-D shapes?',
    'How do I demonstrate understanding of lines?',
  ]],
  ['3.0 Geometry', '3.2 Angles', 6, [
    'What is an angle and how is it measured?',
    'How do we classify angles (acute, right, obtuse, reflex)?',
    'How do we measure angles using a protractor?',
    'How do we construct angles using a protractor?',
    'How do we identify angles in shapes and the environment?',
    'How do I demonstrate understanding of angles?',
  ]],
  ['3.0 Geometry', '3.3 3-D Objects', 6, [
    'What are 3-D objects and how do we identify them?',
    'How do we describe the properties of 3-D objects (faces, edges, vertices)?',
    'How do we classify 3-D objects?',
    'How do we draw nets of 3-D objects?',
    'How do we relate 3-D objects to real-life items?',
    'How do I demonstrate understanding of 3-D objects?',
  ]],

  // ── 4.0 Data Handling ────────────────────────────────────────────────────
  ['4.0 Data Handling', '4.1 Bar Graphs', 10, [
    'What is data and why do we collect it?',
    'How do we collect and record data using a tally?',
    'How do we organize data in a frequency table?',
    'What is a bar graph and what are its components?',
    'How do we draw a bar graph from collected data?',
    'How do we read and interpret a bar graph?',
    'How do we compare information using bar graphs?',
    'How do we answer questions from a bar graph?',
    'How do we draw and interpret horizontal bar graphs?',
    'How do I demonstrate understanding of bar graphs?',
  ]],
];

// ── Insert ───────────────────────────────────────────────────────────────────
$sql = 'INSERT INTO scheme_of_work
            (learning_area_id, week, lesson, strand, sub_strand,
             slo_cd, slo_sow, le_cd, le_sow, key_inquiry, resources, assessment, remarks)
        VALUES
            (:laid, :week, :lesson, :strand, :sub_strand,
             :slo_cd, :slo_sow, :le_cd, :le_sow, :key_inquiry, \'\', \'\', \'\')';
$stmt = $pdo->prepare($sql);

$currentWeek  = 1;
$lessonInWeek = 1;
$inserted     = 0;

foreach ($syllabus as [$strand, $sub_strand, $count, $keyInquiries]) {
    for ($i = 0; $i < $count; $i++) {
        $lessonNum   = $i + 1;
        $keyInquiry  = $keyInquiries[$i] ?? "Lesson $lessonNum: Key inquiry question for $sub_strand.";
        $slo_sow     = "Lesson $lessonNum — $sub_strand\n\nBy the end of this lesson, learners should be able to:\na) [Fill in Specific Learning Outcome (a)]\nb) [Fill in Specific Learning Outcome (b)]";
        $le_sow      = "• [Describe Learning Experience activity for lesson $lessonNum]\n• [Share findings with class]";

        $stmt->execute([
            ':laid'        => $learningAreaId,
            ':week'        => $currentWeek,
            ':lesson'      => $lessonInWeek,
            ':strand'      => $strand,
            ':sub_strand'  => $sub_strand,
            ':slo_cd'      => "[Specific Learning Outcome — Lesson $lessonNum]",
            ':slo_sow'     => $slo_sow,
            ':le_cd'       => "[Core learning experience — Lesson $lessonNum]",
            ':le_sow'      => $le_sow,
            ':key_inquiry' => $keyInquiry,
        ]);
        $inserted++;
        $lessonInWeek++;
        if ($lessonInWeek > $LESSONS_PER_WEEK) {
            $lessonInWeek = 1;
            $currentWeek++;
        }
    }
}

$totalWeeks = $currentWeek - ($lessonInWeek === 1 ? 1 : 0);
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<title>Grade 6 Maths Seed Complete</title>
<style>
  body{font-family:Segoe UI,sans-serif;padding:30px;background:#f3f4f6}
  .card{background:#fff;border-radius:8px;padding:28px;max-width:720px;box-shadow:0 1px 4px rgba(0,0,0,.12)}
  h2{color:#1a56db;margin-bottom:8px}
  table{width:100%;border-collapse:collapse;margin-top:18px}
  th{background:#1a56db;color:#fff;padding:9px 12px;text-align:left}
  td{padding:8px 12px;border-bottom:1px solid #e5e7eb;font-size:13px}
  tr:last-child td{border-bottom:none}
  .btn{display:inline-block;margin-top:20px;background:#1a56db;color:#fff;padding:10px 22px;border-radius:6px;text-decoration:none;font-weight:600}
  .note{background:#fffbeb;border:1px solid #fcd34d;border-radius:6px;padding:12px 16px;font-size:13px;margin-top:18px}
</style></head><body>
<div class="card">
  <h2>&#10003; Grade 6 Mathematics — Seeded Successfully</h2>
  <p>Learning Area: <strong><?= htmlspecialchars($la['name']) ?></strong> (<?= htmlspecialchars($la['grade_name']) ?>)</p>
  <p><strong><?= $inserted ?> lessons</strong> inserted across <strong><?= $totalWeeks ?> weeks</strong> at <?= $LESSONS_PER_WEEK ?> lessons/week.</p>

  <table>
    <tr><th>Strand</th><th>Sub-Strand</th><th>Lessons</th></tr>
    <tr><td>1.0 Numbers</td><td>1.1 Whole Numbers</td><td>20</td></tr>
    <tr><td>1.0 Numbers</td><td>1.2 Multiplication</td><td>6</td></tr>
    <tr><td>1.0 Numbers</td><td>1.3 Division</td><td>6</td></tr>
    <tr><td>1.0 Numbers</td><td>1.4 Fractions</td><td>12</td></tr>
    <tr><td>1.0 Numbers</td><td>1.5 Decimals</td><td>12</td></tr>
    <tr><td>1.0 Numbers</td><td>1.6 Inequalities</td><td>8</td></tr>
    <tr><td>2.0 Measurement</td><td>2.1 Length</td><td>14</td></tr>
    <tr><td>2.0 Measurement</td><td>2.2 Area</td><td>6</td></tr>
    <tr><td>2.0 Measurement</td><td>2.3 Capacity</td><td>6</td></tr>
    <tr><td>2.0 Measurement</td><td>2.4 Mass</td><td>14</td></tr>
    <tr><td>2.0 Measurement</td><td>2.5 Time</td><td>10</td></tr>
    <tr><td>2.0 Measurement</td><td>2.6 Money</td><td>8</td></tr>
    <tr><td>3.0 Geometry</td><td>3.1 Lines</td><td>6</td></tr>
    <tr><td>3.0 Geometry</td><td>3.2 Angles</td><td>6</td></tr>
    <tr><td>3.0 Geometry</td><td>3.3 3-D Objects</td><td>6</td></tr>
    <tr><td>4.0 Data Handling</td><td>4.1 Bar Graphs</td><td>10</td></tr>
    <tr><td colspan="2"><strong>Total</strong></td><td><strong><?= $inserted ?></strong></td></tr>
  </table>

  <div class="note">
    <strong>Note:</strong> SLO (CD), SLO (SOW), Learning Experiences (CD &amp; SOW) have been pre-filled with
    placeholder text. Open the SOW table and use the <em>Edit</em> button on each row to add your full content.
  </div>

  <a class="btn" href="index.php?learning_area_id=<?= $learningAreaId ?>">Open Scheme of Work &rarr;</a>
  &nbsp;
  <a class="btn" style="background:#6b7280" href="curriculum.php">Back to Curriculum</a>
</div>
</body></html>
