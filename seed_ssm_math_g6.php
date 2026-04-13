<?php
// seed_ssm_math_g6.php — Seeds sub_strand_meta for Grade 6 Mathematics
// Extracted from the Grade 6 Mathematics CBC curriculum design document.
// Usage: http://localhost/SCHEME/seed_ssm_math_g6.php?la=YOUR_ID
// Add &force=1 to overwrite existing rows.
require_once 'config.php';
$pdo = getDB();

$laId = isset($_GET['la']) ? (int)$_GET['la'] : 0;

if ($laId < 1) {
    $areas = $pdo->query(
        "SELECT la.id, la.name, la.short_code, g.name AS grade_name
         FROM learning_areas la JOIN grades g ON g.id = la.grade_id
         ORDER BY g.sort_order, la.name"
    )->fetchAll();
    echo "<style>body{font-family:Segoe UI,sans-serif;padding:30px} table{border-collapse:collapse;width:100%;max-width:600px} th,td{border:1px solid #d1d5db;padding:8px 12px} th{background:#1a56db;color:#fff} a{color:#1a56db}</style>";
    echo "<h2>Grade 6 Maths — Curriculum Design Seed</h2>";
    echo "<p style='color:red'>Provide a learning area ID: <code>?la=ID</code></p>";
    echo "<table><tr><th>ID</th><th>Grade</th><th>Learning Area</th><th>Use</th></tr>";
    foreach ($areas as $a) {
        echo "<tr><td>{$a['id']}</td><td>" . htmlspecialchars($a['grade_name']) . "</td><td>" . htmlspecialchars($a['name']) . "</td>";
        echo "<td><a href='seed_ssm_math_g6.php?la={$a['id']}'>Seed here</a></td></tr>";
    }
    echo "</table>"; exit;
}

$laStmt = $pdo->prepare("SELECT la.*, g.name AS grade_name FROM learning_areas la JOIN grades g ON g.id = la.grade_id WHERE la.id = :id");
$laStmt->execute([':id' => $laId]);
$la = $laStmt->fetch();
if (!$la) { echo "<p style='color:red'>Learning area ID $laId not found.</p>"; exit; }

// Check existing
$existing = $pdo->prepare("SELECT COUNT(*) FROM sub_strand_meta WHERE learning_area_id = :id");
$existing->execute([':id' => $laId]);
$existing = $existing->fetchColumn();
if ($existing > 0 && empty($_GET['force'])) {
    echo "<p style='color:orange;font-weight:bold'>Already has $existing sub-strand meta rows.<br>
          <a href='seed_ssm_math_g6.php?la=$laId&force=1' onclick=\"return confirm('Overwrite existing rows?')\">Force re-seed</a> |
          <a href='sub_strand_meta.php?la=$laId'>View Curriculum Design</a></p>"; exit;
}

// ── Curriculum Design Data ─────────────────────────────────────────────────
// Source: KICD Grade 6 Mathematics CBC Curriculum Design (First Published 2017, Revised 2024)
// Text reproduced verbatim from document; OCR scanning errors corrected only.
$data = [

  // ── 1.0 NUMBERS ───────────────────────────────────────────────────────────
  [
    'strand'               => '1.0 Numbers',
    'sub_strand'           => '1.1 Whole Numbers',
    'key_inquiry_qs'       => 'How do we read and write numbers in symbols and in words?',
    'core_competencies'    => 'Critical thinking and problem solving: learners form different numbers by rearranging digits of a given number.',
    'values_attit'         => 'Unity: learners, in pairs or groups, harmoniously identify total value of digits up to millions using place value apparatus.',
    'pcis'                 => "Social cohesion: learners work cohesively with peers and identify the square root of a given number as a value which when multiplied by itself results in the given number.\nSafety: learners work together to create awareness on the levels of congestion on the roads using charts and discuss how to address road safety issues.",
    'links_to_other_areas' => 'Learners read and write numbers in words which is enhanced from skills in Languages.',
    'resources'            => 'Place value apparatus, number charts, number cards, multiplication tables. ICT devices: Learner digital devices (LDD), teacher digital devices (TDD), mobile phones, digital clocks, television sets, videos, cameras, projectors, radios, DVD players, CDs, scanners, internet among others.',
  ],
  [
    'strand'               => '1.0 Numbers',
    'sub_strand'           => '1.2 Multiplication',
    'key_inquiry_qs'       => 'How do we multiply numbers?',
    'core_competencies'    => 'Creativity and imagination: learner makes patterns involving multiplication with products not exceeding 1,000 using number cards.',
    'values_attit'         => 'Integrity: Learner multiplies up to a 4-digit number by a 2-digit number using skip counting and demonstrates honesty in their results.',
    'pcis'                 => 'Self-esteem: Learner develops confidence as they estimate products  using rounding off factors which builds self-esteem.',
    'links_to_other_areas' => 'Learner estimates quantities of seeds or fertiliser required for planting different crops as learnt from Agriculture.',
    'resources'            => 'Multiplication tables. ICT devices: Learner digital devices (LDD), teacher digital devices (TDD), mobile phones, digital clocks, television sets, videos, cameras, projectors, radios, DVD players, CDs, scanners, internet among others.',
  ],
  [
    'strand'               => '1.0 Numbers',
    'sub_strand'           => '1.3 Division',
    'key_inquiry_qs'       => 'Where is division used in real life?',
    'core_competencies'    => 'Communication and collaboration: learner discusses with peers the relationship between multiplication and division using examples.',
    'values_attit'         => 'Unity: learner works together with others amicably to divide up to a 4-digit number by up to a 3-digit number and shares answers.',
    'pcis'                 => 'Learner divides whole numbers using digital devices or other resources as they observe safety.',
    'links_to_other_areas' => 'Learner divides quantities such as ingredients for cooking as learnt from Agriculture.',
    'resources'            => 'Multiplication tables. ICT devices: Learner digital devices (LDD), teacher digital devices (TDD), mobile phones, digital clocks, television sets, videos, cameras, projectors, radios, DVD players, CDs, scanners, internet among others.',
  ],
  [
    'strand'               => '1.0 Numbers',
    'sub_strand'           => '1.4 Fractions',
    'key_inquiry_qs'       => "1. How do we add or subtract fractions?\n2. Where is percentage used in day-to-day life?",
    'core_competencies'    => 'Learning to learn: learner works out the reciprocal of whole numbers before solving the reciprocal of proper fractions.',
    'values_attit'         => 'Unity: learner works harmoniously with peers and discusses reciprocals of proper fractions.',
    'pcis'                 => 'Learner cohesively works together with others to calculate squares of fractions through multiplication to enhance social cohesion.',
    'links_to_other_areas' => 'Learner uses fractional parts of a canvas or drawing materials to draw different patterns as learnt from Creative Arts.',
    'resources'            => 'Equivalent fraction board, circular and rectangular cut-outs, counters. ICT devices: Learner digital devices (LDD), teacher digital devices (TDD), mobile phones, digital clocks, television sets, videos, cameras, projectors, radios, DVD players, CDs, scanners, internet among others.',
  ],
  [
    'strand'               => '1.0 Numbers',
    'sub_strand'           => '1.5 Decimals',
    'key_inquiry_qs'       => 'Where are decimals applicable in real life?',
    'core_competencies'    => 'Communication and collaboration: learner discusses and relates place value of decimals up to ten thousandths to the number of decimal places.',
    'values_attit'         => 'Responsibility: learner adds decimals up to 4-decimal places using place value apparatus and shows responsibility by taking care of the apparatus.',
    'pcis'                 => 'Learner adds decimals up to 4-decimal places using place value apparatus and share answers or working strategies with one another as part of Peer education.',
    'links_to_other_areas' => 'Learner acquires new mathematical terms as they discuss and round off decimals up to 3 decimal places as acquired from Languages.',
    'resources'            => 'Place value charts, number cards. ICT devices: Learner digital devices (LDD), teacher digital devices (TDD), mobile phones, digital clocks, television sets, videos, cameras, projectors, radios, DVD players, CDs, scanners, internet among others.',
  ],
  [
    'strand'               => '1.0 Numbers',
    'sub_strand'           => '1.6 Inequalities',
    'key_inquiry_qs'       => 'How do we solve simple inequalities?',
    'core_competencies'    => 'Self-efficacy: learner confidently works out simple inequalities involving one unknown.',
    'values_attit'         => 'Responsibility: as learner works with peers to use IT devices carefully to simplify inequalities.',
    'pcis'                 => 'Social cohesion: Learner works together with others harmoniously to form inequalities in one unknown to enhance social cohesion.',
    'links_to_other_areas' => 'Learner uses new terms used in inequalities to enhance vocabulary in Languages.',
    'resources'            => 'Digital inequality worksheets; greater than, less than or equal to, sorting cards. ICT devices: Learner digital devices (LDD), teacher digital devices (TDD), mobile phones, digital clocks, television sets, videos, cameras, projectors, radios, DVD players, CDs, scanners, internet among others.',
  ],

  // ── 2.0 MEASUREMENT ───────────────────────────────────────────────────────
  [
    'strand'               => '2.0 Measurement',
    'sub_strand'           => '2.1 Length',
    'key_inquiry_qs'       => "1. Why do we measure distances in day-to-day life?\n2. What do we use to measure length in real life?",
    'core_competencies'    => 'Creativity and imagination: learner sketches the circumference, diameter and radius of a circle practically.',
    'values_attit'         => 'Unity: learner works amicably with peers to determine lengths in centimetres and millimetres in addition, subtraction, multiplication and division and discuss the answers.',
    'pcis'                 => 'Learner chooses appropriate units for measuring lengths of different objects in the environment as enhanced from Environmental Education.',
    'links_to_other_areas' => 'Learner handles objects with care when measuring lengths of different objects in the school compound for play activities in Creative Arts.',
    'resources'            => 'Metre rule, 1 metre sticks, tape measure. ICT devices: Learner digital devices (LDD), teacher digital devices (TDD), mobile phones, digital clocks, television sets, videos, cameras, projectors, radios, DVD players, CDs, scanners, internet among others.',
  ],
  [
    'strand'               => '2.0 Measurement',
    'sub_strand'           => '2.2 Area',
    'key_inquiry_qs'       => 'Where is area used in real life?',
    'core_competencies'    => 'Creativity and imagination: learner works out the area of triangles in cm2 using the relationship between a rectangle and a triangle.',
    'values_attit'         => 'Love: learner sketches a circle on a unit square grid and counts the full squares to estimate the area of circles and compare answers with one another.',
    'pcis'                 => 'Learner confidently establishes that the area of a triangle is equal to a half of the area of a rectangle or a square when the rectangle or the square is divided by a diagonal to enhance self-esteem.',
    'links_to_other_areas' => 'Learner explores their environment to calculate area of different places such as play fields within the community as learnt in Social Studies.',
    'resources'            => 'Square cut-outs, 1 cm squares, 1 m squares. ICT devices: Learner digital devices (LDD), teacher digital devices (TDD), mobile phones, digital clocks, television sets, videos, cameras, projectors, radios, DVD players, CDs, scanners, internet among others.',
  ],
  [
    'strand'               => '2.0 Measurement',
    'sub_strand'           => '2.3 Capacity',
    'key_inquiry_qs'       => "1. How can we measure capacity?\n2. Where is capacity applicable in real life?",
    'core_competencies'    => 'Critical thinking and problem solving: learner works out the relationship between cm3,  millilitres and litres through measuring capacities practically.',
    'values_attit'         => 'Peace: learner works together with others harmoniously to measure capacity in millilitres and litres and agree on answers.',
    'pcis'                 => 'Learner changes capacity in litres to millilitres using containers from the environment as part of Environmental education.',
    'links_to_other_areas' => 'Learner takes accurate measurements of liquids using different containers from the immediate environment as part of Science and Technology.',
    'resources'            => 'Tea spoons, containers of different sizes, water, sand, soil. ICT devices: Learner digital devices (LDD), teacher digital devices (TDD), mobile phones, digital clocks, television sets, videos, cameras, projectors, radios, DVD players, CDs, scanners, internet among others.',
  ],
  [
    'strand'               => '2.0 Measurement',
    'sub_strand'           => '2.4 Mass',
    'key_inquiry_qs'       => "1. How can we measure large amounts of mass?\n2. In what situations would the tonnes be more applicable to use when measuring mass?",
    'core_competencies'    => 'Digital literacy: learner uses digital weighing machines to measure mass of different items.',
    'values_attit'         => 'Integrity: learner honestly determines mass of items in kilogrammes using different operations involving addition, subtraction, multiplication and division.',
    'pcis'                 => 'Learner discusses with others items in the environment such as loaded lorries, whose mass may be measured in tonnes and their impact on roads as learnt in Environmental education.',
    'links_to_other_areas' => 'Learner discusses with others transit trucks that carry grains in tonnes to different places as learnt in Social Studies.',
    'resources'            => 'Tea spoons, soil or sand, manual/electronic weighing machine, beam balance. ICT devices: Learner digital devices (LDD), teacher digital devices (TDD), mobile phones, digital clocks, television sets, videos, cameras, projectors, radios, DVD players, CDs, scanners, internet among others.',
  ],
  [
    'strand'               => '2.0 Measurement',
    'sub_strand'           => '2.5 Time',
    'key_inquiry_qs'       => 'How do we read and tell time?',
    'core_competencies'    => 'Learning to learn: learner determines time in a.m. and p.m. from digital and analogue clocks.',
    'values_attit'         => 'Integrity: learner observes time in various activities and is punctual.',
    'pcis'                 => 'Learner discusses the transit trucks that carry grains in tonnes to different places as learnt from Social Studies. Learners determine time durations of travelling using travel timetables within the country as enhanced in Citizenship.',
    'links_to_other_areas' => 'Learner records time taken to perform in different games such as athletics as done in Creative Arts.',
    'resources'            => 'Analogue and digital clocks, digital watches, stopwatches. ICT devices: Learner digital devices (LDD), teacher digital devices (TDD), mobile phones, digital clocks, television sets, videos, cameras, projectors, radios, DVD players, CDs, scanners, internet among others.',
  ],
  [
    'strand'               => '2.0 Measurement',
    'sub_strand'           => '2.6 Money',
    'key_inquiry_qs'       => 'How can you make profit in a business?',
    'core_competencies'    => 'Communication and collaboration: Learner discusses with others the meaning of profit and loss in real-life situations and shares with peers.',
    'values_attit'         => 'Integrity: Learner honestly determines buying and selling prices of different items in their classroom model shop.',
    'pcis'                 => 'Learner discusses with others income and value added tax (VAT) as a form of tax as part of financial literacy.',
    'links_to_other_areas' => 'Learner participates in making budgets for buying food at home as learnt from Agriculture.',
    'resources'            => 'Price list, classroom shop, electronic money tariff charts. ICT devices: Learner digital devices (LDD), teacher digital devices (TDD), mobile phones, digital clocks, television sets, videos, cameras, projectors, radios, DVD players, CDs, scanners, internet among others.',
  ],

  // ── 3.0 GEOMETRY ──────────────────────────────────────────────────────────
  [
    'strand'               => '3.0 Geometry',
    'sub_strand'           => '3.1 Lines',
    'key_inquiry_qs'       => 'Why do we need to draw lines?',
    'core_competencies'    => 'Creativity and imagination: as learner bisects lines using ruler and compasses.',
    'values_attit'         => 'Responsibility: Learner carefully shares digital devices and other resources to draw parallel lines.',
    'pcis'                 => 'Learner exercises caution as they use geometrical instruments in construction of parallel lines as they observe safety measures.',
    'links_to_other_areas' => 'Learner constructs lines that can be used in creative drawing as part of Creative Arts.',
    'resources'            => 'Chalkboard ruler, 30 cm ruler, straight edges. ICT devices: Learner digital devices (LDD), teacher digital devices (TDD), mobile phones, digital clocks, television sets, videos, cameras, projectors, radios, DVD players, CDs, scanners, internet among others.',
  ],
  [
    'strand'               => '3.0 Geometry',
    'sub_strand'           => '3.2 Angles',
    'key_inquiry_qs'       => 'Where can you use angles in real life?',
    'core_competencies'    => 'Self-efficacy: Learner confidently and practically establishes sum of the interior angles in a rectangle and triangle.',
    'values_attit'         => 'Unity: Learner works harmoniously with others to compare the sizes of angles.',
    'pcis'                 => 'Learner practically establishes the sum of angles in a triangle and rectangles from different objects in the environment as enhanced in Environmental education.',
    'links_to_other_areas' => 'Learner draws lines and angles that can be used in drawing and painting in Creative Art.',
    'resources'            => 'Unit angles, protractors, rulers. ICT devices: Learner digital devices (LDD), teacher digital devices (TDD), mobile phones, digital clocks, television sets, videos, cameras, projectors, radios, DVD players, CDs, scanners, internet among others.',
  ],
  [
    'strand'               => '3.0 Geometry',
    'sub_strand'           => '3.3 3-D Objects',
    'key_inquiry_qs'       => 'How do we use containers in daily life?',
    'core_competencies'    => 'Creativity and imagination: Learner opens up nets of cuboids, cubes and cylinders.',
    'values_attit'         => 'Learner discusses with others and collect 3-D objects and safely keep them as part of their role in environmental conservation to enhance Patriotism.',
    'pcis'                 => 'Learner discusses with others rectangular, square and circular shapes on the nets and respect each other\'s views as part of social cohesion.',
    'links_to_other_areas' => 'Learner discusses with others the differences between 3-D objects in terms of faces, edges and vertices in drawing and improves language skills.',
    'resources'            => 'Cubes, cuboids, cylinders, pyramids, spheres, cut-outs of rectangles, circles, and triangles of different sizes. ICT devices: Learner digital devices (LDD), teacher digital devices (TDD), mobile phones, digital clocks, television sets, videos, cameras, projectors, radios, DVD players, CDs, scanners, internet among others.',
  ],

  // ── 4.0 DATA HANDLING ─────────────────────────────────────────────────────
  [
    'strand'               => '4.0 Data Handling',
    'sub_strand'           => '4.1 Bar Graphs',
    'key_inquiry_qs'       => 'How can bar graphs be used in real-life situations?',
    'core_competencies'    => 'Creativity and imagination: as learner discusses with others and collects data and organises it using pictographs.',
    'values_attit'         => 'Integrity: Learner piles similar objects such as match boxes vertically to honestly represent data.',
    'pcis'                 => 'Learner collects data on identified topic from immediate environment to address community issues as part of non-formal education.',
    'links_to_other_areas' => 'Learner gathers information on any items in the environment that will enhance learning in Science and Technology.',
    'resources'            => 'Bar graph worksheets, data graph worksheets, data samples from different sources. ICT devices: Learner digital devices (LDD), teacher digital devices (TDD), mobile phones, digital clocks, television sets, videos, cameras, projectors, radios, DVD players, CDs, scanners, internet among others.',
  ],
];

// ── Insert / Update ────────────────────────────────────────────────────────
$sql = "INSERT INTO sub_strand_meta
            (learning_area_id, strand, sub_strand,
             key_inquiry_qs, core_competencies, values_attit, pcis,
             links_to_other_areas, resources)
        VALUES
            (:la, :strand, :ss,
             :kiq, :cc, :val, :pcis, :links, :res)
        ON DUPLICATE KEY UPDATE
            key_inquiry_qs       = VALUES(key_inquiry_qs),
            core_competencies    = VALUES(core_competencies),
            values_attit         = VALUES(values_attit),
            pcis                 = VALUES(pcis),
            links_to_other_areas = VALUES(links_to_other_areas),
            resources            = VALUES(resources)";

$stmt = $pdo->prepare($sql);
$inserted = 0;
foreach ($data as $r) {
    $stmt->execute([
        ':la'     => $laId,
        ':strand' => $r['strand'],
        ':ss'     => $r['sub_strand'],
        ':kiq'    => $r['key_inquiry_qs'],
        ':cc'     => $r['core_competencies'],
        ':val'    => $r['values_attit'],
        ':pcis'   => $r['pcis'],
        ':links'  => $r['links_to_other_areas'],
        ':res'    => $r['resources'],
    ]);
    $inserted++;
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<title>Curriculum Design Seed</title>
<style>
  body{font-family:Segoe UI,sans-serif;padding:30px;background:#f3f4f6}
  .card{background:#fff;border-radius:8px;padding:28px;max-width:640px;box-shadow:0 1px 4px rgba(0,0,0,.12)}
  h2{color:#1a56db;margin-bottom:8px}
  table{width:100%;border-collapse:collapse;margin-top:18px;font-size:13px}
  th{background:#1a56db;color:#fff;padding:9px 12px;text-align:left}
  td{padding:8px 12px;border-bottom:1px solid #e5e7eb}
  tr:last-child td{border-bottom:none}
  .dot-ok{color:#059669} .dot-no{color:#d1d5db}
  .btn{display:inline-block;margin-top:20px;background:#1a56db;color:#fff;padding:10px 22px;border-radius:6px;text-decoration:none;font-weight:600}
  .note{background:#fffbeb;border:1px solid #fcd34d;border-radius:6px;padding:12px 16px;font-size:13px;margin-top:18px}
</style></head><body>
<div class="card">
  <h2>&#10003; Grade 6 Mathematics — Curriculum Design Seeded</h2>
  <p>Learning Area: <strong><?= htmlspecialchars($la['name']) ?></strong> (<?= htmlspecialchars($la['grade_name']) ?>)</p>
  <p><strong><?= $inserted ?></strong> sub-strand rows inserted/updated.</p>

  <table>
    <tr><th>Sub-Strand</th><th>KIQ</th><th>CC</th><th>Val</th><th>PCIs</th><th>Links</th><th>Res</th></tr>
    <?php foreach ($data as $r):
      $d = fn($f) => trim($r[$f]) !== '' ? '<span class="dot-ok">&#9679;</span>' : '<span class="dot-no">&#9675;</span>';
    ?>
    <tr>
      <td><?= htmlspecialchars($r['sub_strand']) ?></td>
      <td><?= $d('key_inquiry_qs') ?></td>
      <td><?= $d('core_competencies') ?></td>
      <td><?= $d('values_attit') ?></td>
      <td><?= $d('pcis') ?></td>
      <td><?= $d('links_to_other_areas') ?></td>
      <td><?= $d('resources') ?></td>
    </tr>
    <?php endforeach; ?>
  </table>

  <div class="note" style="background:#d1fae5;border-color:#6ee7b7;color:#065f46">
    <strong>Complete.</strong> All 16 sub-strands have been seeded with full curriculum design data
    (Key Inquiry Questions, Core Competencies, Values, PCIs, Links to Other Areas, Resources).
  </div>

  <a class="btn" href="sub_strand_meta.php?la=<?= $laId ?>">View Curriculum Design &rarr;</a>
  &nbsp;
  <a class="btn" style="background:#6b7280" href="curriculum.php">Back to Curriculum</a>
</div>
</body></html>
