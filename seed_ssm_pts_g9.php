<?php
// seed_ssm_pts_g9.php — Seeds sub_strand_meta for Grade 9 Pre-Technical Studies
// Source: KICD Grade 9 Pre-Technical Studies CBC Curriculum Design (Revised 2024)
// Usage: http://localhost/SCHEME/seed_ssm_pts_g9.php?la=1
// Add &force=1 to overwrite existing rows.
require_once 'config.php';
$pdo = getDB();

$laId = isset($_GET['la']) ? (int)$_GET['la'] : 0;

if ($laId < 1) {
    $areas = $pdo->query(
        "SELECT la.id, la.name, la.short_code, g.name AS grade_name
         FROM learning_areas la JOIN grades g ON g.id = la.grade_id
         ORDER BY la.id"
    )->fetchAll(PDO::FETCH_ASSOC);
    echo "<style>body{font-family:Segoe UI,sans-serif;padding:30px}table{border-collapse:collapse;width:100%;max-width:640px}th,td{border:1px solid #d1d5db;padding:8px 12px}th{background:#1a56db;color:#fff}a{color:#1a56db}</style>";
    echo "<h2>Grade 9 Pre-Technical Studies — Curriculum Design Seed</h2>";
    echo "<p style='color:red'>Provide a learning area ID: <code>?la=ID</code></p>";
    echo "<table><tr><th>ID</th><th>Grade</th><th>Learning Area</th><th>Use</th></tr>";
    foreach ($areas as $a) {
        echo "<tr><td>{$a['id']}</td><td>" . htmlspecialchars($a['grade_name']) . "</td><td>" . htmlspecialchars($a['name']) . "</td>";
        echo "<td><a href='seed_ssm_pts_g9.php?la={$a['id']}'>Seed here</a></td></tr>";
    }
    echo "</table>"; exit;
}

$laStmt = $pdo->prepare("SELECT la.*, g.name AS grade_name FROM learning_areas la JOIN grades g ON g.id = la.grade_id WHERE la.id = :id");
$laStmt->execute([':id' => $laId]);
$la = $laStmt->fetch();
if (!$la) { echo "<p style='color:red'>Learning area ID $laId not found.</p>"; exit; }

$existing = $pdo->prepare("SELECT COUNT(*) FROM sub_strand_meta WHERE learning_area_id = :id");
$existing->execute([':id' => $laId]);
$existing = (int)$existing->fetchColumn();
if ($existing > 0 && empty($_GET['force'])) {
    echo "<p style='color:orange;font-weight:bold'>Already has $existing sub-strand meta row(s).<br>
          <a href='seed_ssm_pts_g9.php?la=$laId&force=1' onclick=\"return confirm('Overwrite existing rows?')\">Force re-seed</a> |
          <a href='sub_strand_meta.php?la=$laId'>View Curriculum Design</a></p>"; exit;
}

// ── Curriculum Design Data ─────────────────────────────────────────────────
// Source: KICD Grade 9 Pre-Technical Studies CBC Curriculum Design
$data = [

  // ── 1.0 FOUNDATIONS OF PRE-TECHNICAL STUDIES ─────────────────────────────
  [
    'strand'               => '1.0 Foundations of Pre-Technical Studies',
    'sub_strand'           => '1.1 Safety on Raised Platforms',
    'key_inquiry_qs'       => "What risks are associated with working on raised platforms?\nHow do we stay safe when working at heights?",
    'core_competencies'    => "Critical thinking and problem solving: learners identify, assess and evaluate risks associated with working on raised platforms and propose appropriate safety measures.\nSelf-efficacy: learners demonstrate confidence in applying personal protective equipment and safe practices when working at heights.",
    'values_attit'         => "Responsibility: learners take personal responsibility for their own safety and the safety of others when working on raised platforms.\nCare: learners show concern for the wellbeing of colleagues by following and promoting safety protocols at the workplace.",
    'pcis'                 => "Safety education: learners study hazards of working at heights and apply precautionary measures including use of PPE.\nOccupational safety and health (OSH): learners connect safety on raised platforms to broader workplace health and safety standards.\nEnvironmental education: learners appreciate the importance of safe, sustainable working environments.",
    'links_to_other_areas' => 'Science and Technology: learners apply concepts of forces, gravity and balance studied in Science to understand risks on raised platforms. Social Studies: learners discuss community occupations that require working at heights.',
    'resources'            => "Photographs and charts of raised platforms (ladders, trestles, scaffolding, work-benches, ramps)\nPPE samples or pictures (helmets, harnesses, safety boots, gloves)\nPrint or digital media articles on workplace accidents\nTextbook, chalkboard, chalk",
    'assessment'           => "Oral questions: identify types of raised platforms and their associated risks.\nObservation checklist: correct demonstration of PPE use during role play.\nWritten exercise: propose safety measures for a given raised-platform scenario.",
  ],
  [
    'strand'               => '1.0 Foundations of Pre-Technical Studies',
    'sub_strand'           => '1.2 Handling Hazardous Substances',
    'key_inquiry_qs'       => "What hazardous substances are found in our environment?\nHow do we safely handle, store and dispose of hazardous substances?",
    'core_competencies'    => "Critical thinking and problem solving: learners classify hazardous substances by their properties and determine appropriate safe-handling procedures for each class.\nDigital literacy: learners use print and digital media to research safety data sheets and hazard symbols for various substances.",
    'values_attit'         => "Responsibility: learners observe safety procedures when handling hazardous substances to protect themselves and the community.\nCare: learners demonstrate concern for environmental health by disposing of hazardous waste correctly.\nIntegrity: learners honestly follow prescribed safety instructions rather than taking shortcuts.",
    'pcis'                 => "Safety education: learners interpret hazard warning symbols, safety labels and MSDS information on hazardous containers.\nEnvironmental education: learners discuss improper disposal of hazardous substances and its impact on the environment.\nHealth education: learners relate exposure to hazardous substances to health risks including chemical burns, poisoning and respiratory problems.",
    'links_to_other_areas' => 'Science and Technology: learners apply knowledge of chemical properties (flammable, corrosive, toxic) from chemistry topics. Agriculture: learners connect safe agrochemical handling to this sub-strand.',
    'resources'            => "Sample containers with hazard symbols (empty, safe)\nCharts showing hazard pictograms (GHS/COSHH symbols)\nSafety data sheets (printouts)\nPPE: gloves, goggles, apron pictures or samples\nTextbook, chalkboard, chalk",
    'assessment'           => "Identification exercise: match hazardous substances to their hazard class (poisonous, flammable, corrosive).\nPractical observation: evaluate correct PPE selection and handling technique during simulated activity.\nWritten quiz: interpret safety symbols and state precautions for a listed hazardous substance.",
  ],
  [
    'strand'               => '1.0 Foundations of Pre-Technical Studies',
    'sub_strand'           => '1.3 Self-Exploration and Career Development',
    'key_inquiry_qs'       => "What are my talents and abilities?\nHow do my talents relate to career pathways in Pre-Technical Studies?",
    'core_competencies'    => "Self-efficacy: learners reflect on personal talents and abilities and develop confidence in pursuing relevant career pathways.\nCommunication and collaboration: learners discuss career options in groups, listen to resource persons and present findings to the class.\nCitizenship: learners understand how individual talents contribute to national development and the economy.",
    'values_attit'         => "Respect: learners appreciate the diverse talents and abilities of their peers and treat all career pathways with equal value.\nResponsibility: learners commit to nurturing their talents and making informed career decisions.\nUnity: learners collaborate in identifying shared talents and supporting each other's aspirations.",
    'pcis'                 => "Career guidance and counselling: learners map personal talents and abilities to Pre-Technical Studies career pathways (construction, manufacturing, ICT, entrepreneurship).\nLife skills education: learners develop self-awareness, goal-setting and decision-making skills related to career choices.\nCitizenship education: learners appreciate how technical careers contribute to the social and economic development of Kenya.",
    'links_to_other_areas' => 'Social Studies: learners connect career exploration to the study of the economy and occupational sectors in Kenya. Life Skills: values of self-awareness and goal-setting are reinforced.',
    'resources'            => "Career pathway charts and posters for Pre-Technical Studies fields\nGuest speaker / resource person (artisan, technician, entrepreneur)\nNewspaper and magazine articles on technical careers\nTextbook, chalkboard, chalk",
    'assessment'           => "Personal reflection activity: learners write a brief description of two personal talents and a corresponding career pathway.\nGroup presentation: present and justify their chosen career pathways to the class.\nOral question and answer: relate specific talents to Pre-Technical Studies career opportunities.",
  ],

  // ── 2.0 COMMUNICATION IN PRE-TECHNICAL STUDIES ───────────────────────────
  [
    'strand'               => '2.0 Communication in Pre-Technical Studies',
    'sub_strand'           => '2.1 Oblique Projection',
    'key_inquiry_qs'       => "What is oblique projection?\nHow do we use oblique projection to communicate technical ideas?",
    'core_competencies'    => "Creativity and imagination: learners produce neat oblique projection drawings of given objects, applying correct angles, proportions and line types.\nCritical thinking and problem solving: learners interpret oblique drawings to visualise three-dimensional objects and solve spatial problems.",
    'values_attit'         => "Responsibility: learners take care of drawing tools and produce accurate, tidy work.\nIntegrity: learners present original drawings and acknowledge sources of reference.",
    'pcis'                 => "Education for sustainable development (ESD): learners use drawing skills to design and communicate ideas for sustainable building and product design.\nICT integration: learners use digital drawing tools where available to create oblique projection drawings.\nInnovation and creativity: learners apply oblique projection to communicate innovative design ideas.",
    'links_to_other_areas' => 'Mathematics: learners apply measurement, scale and geometric concepts when constructing oblique drawings. Art and Craft: learners transfer spatial visualisation and drawing skills from Art.',
    'resources'            => "Drawing boards, T-squares, set squares, compasses\nDrawing pencils (H, HB, 2H), erasers, rulers\nProtractors and drawing paper / graph paper\nChalkboard diagrams and model objects\nTextbook",
    'assessment'           => "Practical drawing task: produce a neat oblique projection drawing of a given simple object.\nOral questions: identify and explain the angles and proportions used in oblique projection.\nPeer assessment: evaluate a classmate's drawing against given criteria (accuracy, neatness, labelling).",
  ],
  [
    'strand'               => '2.0 Communication in Pre-Technical Studies',
    'sub_strand'           => '2.2 Visual Programming',
    'key_inquiry_qs'       => "What is visual programming?\nHow do we use visual programming to solve problems?",
    'core_competencies'    => "Digital literacy: learners use block-based visual programming environments (e.g., Scratch, MIT App Inventor) to create simple programs and animations.\nCritical thinking and problem solving: learners design, test and debug visual programs to solve given problems.\nCreativity and imagination: learners create original animations, stories or games using visual programming tools.",
    'values_attit'         => "Responsibility: learners handle ICT equipment with care and follow acceptable use policies.\nIntegrity: learners produce original programs and give credit for shared code resources.",
    'pcis'                 => "ICT integration: learners apply digital literacy skills through hands-on use of visual programming software.\nInnovation and creativity: learners use computational thinking to design creative solutions to everyday problems.\nLife skills education: learners develop logical sequencing, persistence and collaborative problem-solving skills through programming.",
    'links_to_other_areas' => 'Mathematics: learners apply sequencing, logic and geometry concepts in programming tasks. Computer Studies: visual programming concepts form a bridge to text-based programming introduced in Computer Studies.',
    'resources'            => "Computer laboratory or tablets with Scratch / block-based IDE installed\nProjector or television for demonstration\nPrinted Scratch activity cards / worksheets\nTextbook, chalkboard",
    'assessment'           => "Practical task: create a visual program that performs a specified sequence of actions.\nOral explanation: describe the logic and sequence of their program to the class.\nPortfolio: save and present a completed visual program project with a brief description.",
  ],

  // ── 3.0 MATERIALS FOR PRODUCTION ─────────────────────────────────────────
  [
    'strand'               => '3.0 Materials for Production',
    'sub_strand'           => '3.1 Wood',
    'key_inquiry_qs'       => "What types of wood are used in production?\nHow do we select and prepare wood for various production tasks?",
    'core_competencies'    => "Critical thinking and problem solving: learners evaluate different wood types based on properties such as hardness, grain and durability, then select the most appropriate for a given task.\nCreativity and imagination: learners design and plan wood-based products that meet specified requirements.",
    'values_attit'         => "Responsibility: learners handle wood and woodworking tools safely, following all prescribed safety procedures.\nCare: learners demonstrate respect for natural resources by using wood economically and avoiding wastage.\nPatriotism: learners appreciate local Kenyan wood species and promote sustainable use of forest resources.",
    'pcis'                 => "Environmental education: learners discuss sustainable timber harvesting, afforestation and the impact of deforestation on the environment.\nESD: learners relate responsible use of wood to sustainable production and consumption practices.\nSafety education: learners apply safe tool and material handling practices when working with wood.",
    'links_to_other_areas' => 'Science and Technology: learners apply knowledge of material properties from the topic on materials in Science. Agriculture and Nutrition: learners link tree farming to wood availability and sustainable land use.',
    'resources'            => "Wood samples: softwood (e.g., cypress, pine) and hardwood (e.g., mahogany, mvuli)\nMeasuring tape, ruler, try-square\nChalkboard charts on wood types and properties\nTextbook",
    'assessment'           => "Identification exercise: classify given wood samples into softwood or hardwood and justify the choice.\nOral questions: describe the properties and uses of specified wood types.\nShort written test: explain how to select appropriate wood for a given production task.",
  ],
  [
    'strand'               => '3.0 Materials for Production',
    'sub_strand'           => '3.2 Handling Waste Materials',
    'key_inquiry_qs'       => "What waste materials are generated in production activities?\nHow can we reduce, reuse and recycle waste materials in Pre-Technical Studies?",
    'core_competencies'    => "Citizenship: learners take collective responsibility for waste management and promote environmentally responsible production practices in school and community.\nCritical thinking and problem solving: learners identify categories of waste generated in production workshops and propose effective waste-reduction strategies.",
    'values_attit'         => "Responsibility: learners dispose of production waste correctly and follow workshop waste-management procedures.\nCare: learners demonstrate concern for the environment by minimising waste and recycling usable materials.\nPatriotism: learners contribute to a clean, safe community environment by practising responsible waste disposal.",
    'pcis'                 => "Environmental education: learners study the impact of waste on the environment and propose sustainable waste management solutions (reduce, reuse, recycle).\nESD: learners relate responsible waste handling to sustainable production, consumption and the Green Economy.\nHealth education: learners discuss health hazards of improper waste disposal in production environments.",
    'links_to_other_areas' => 'Science and Technology: learners apply knowledge of biodegradable and non-biodegradable materials from Science. Agriculture: composting of biodegradable waste links to Agriculture lessons.',
    'resources'            => "Waste material samples (wood shavings, metal offcuts, plastic scraps, paper)\nWaste sorting bins (labelled: organic, recyclable, hazardous, general)\nCharts on waste classification and the 3R hierarchy\nTextbook, chalkboard",
    'assessment'           => "Sorting activity: correctly categorise given waste materials into appropriate disposal groups.\nGroup discussion report: present a proposed waste-management plan for the school workshop.\nOral questions: explain the environmental impact of improper waste disposal and suggest remedies.",
  ],

  // ── 4.0 TOOLS AND PRODUCTION ─────────────────────────────────────────────
  [
    'strand'               => '4.0 Tools and Production',
    'sub_strand'           => '4.1 Holding Tools',
    'key_inquiry_qs'       => "What are holding tools?\nHow do we select and safely use holding tools in production tasks?",
    'core_competencies'    => "Critical thinking and problem solving: learners select the most appropriate holding tool for a given material and task, justifying their choices based on tool function and material properties.\nSelf-efficacy: learners demonstrate correct and confident use of holding tools during practical activities.",
    'values_attit'         => "Responsibility: learners use, clean and store holding tools correctly to prolong tool life and prevent accidents.\nCare: learners show concern for peers by working safely and observing workshop rules when using holding tools.\nIntegrity: learners honestly report any damage to tools rather than concealing it.",
    'pcis'                 => "Safety education: learners identify hazards associated with incorrect use of holding tools and apply safe operating procedures.\nOccupational safety and health (OSH): learners relate proper tool use to workplace injury prevention.\nCareer guidance: learners connect correct use of hand tools to careers in carpentry, welding and metal fabrication.",
    'links_to_other_areas' => 'Science and Technology: learners apply concepts of force and mechanical advantage from Science when selecting and using tools. Mathematics: learners use measurement skills when setting up work pieces in vices and clamps.',
    'resources'            => "Holding tools: bench vice, G-clamp, sash clamp, hand vice, pliers (flat-nose, round-nose, combination)\nWork bench\nSafety charts for tool use\nTextbook, chalkboard",
    'assessment'           => "Identification test: name given holding tools and state their specific uses.\nPractical demonstration: correctly set up a work piece in a vice or clamp and explain the technique used.\nOral questions: describe safety procedures to observe when using a specified holding tool.",
  ],
  [
    'strand'               => '4.0 Tools and Production',
    'sub_strand'           => '4.2 Driving Tools',
    'key_inquiry_qs'       => "What are driving tools?\nHow do we select and safely use driving tools in production activities?",
    'core_competencies'    => "Critical thinking and problem solving: learners evaluate different driving tools (hammers, mallets, screwdrivers, wrenches) to determine the most suitable tool for a given fastening task.\nSelf-efficacy: learners confidently demonstrate correct grip, posture and technique when using driving tools on practical tasks.",
    'values_attit'         => "Responsibility: learners maintain driving tools in good working condition and store them correctly after use.\nCare: learners observe safety rules that protect themselves and their peers from tool-related injuries.\nIntegrity: learners report any defective or damaged tools immediately to prevent accidents.",
    'pcis'                 => "Safety education: learners identify hazards of using damaged or incorrectly selected driving tools and describe precautions.\nOSH: learners apply occupational health and safety standards to driving-tool use in the workshop.\nCareer guidance: learners relate driving-tool skills to careers in construction, motor vehicle mechanics and manufacturing.",
    'links_to_other_areas' => 'Science and Technology: learners apply concepts of force, torque and leverage from Physics when using hammers and wrenches. Technical Drawing: fastener symbols studied in drawing relate to the fasteners driven in this sub-strand.',
    'resources'            => "Driving tools: claw hammer, mallet, cross-pein hammer, flat-head and cross-head screwdrivers, adjustable wrench, spanner set\nWork bench, nails, screws, bolts and nuts (samples)\nSafety charts\nTextbook, chalkboard",
    'assessment'           => "Identification: name given driving tools and state their correct use.\nPractical task: drive a nail or fix a screw into a work piece using correct technique and posture.\nOral questions: explain safety precautions to observe when using a hammer or screwdriver.",
  ],
  [
    'strand'               => '4.0 Tools and Production',
    'sub_strand'           => '4.3 Project',
    'key_inquiry_qs'       => "How do we apply Pre-Technical Studies skills to design and make a useful product?\nWhat makes a project successful?",
    'core_competencies'    => "Creativity and imagination: learners design original, functional products that address real needs using materials and tools studied in the strand.\nCritical thinking and problem solving: learners evaluate their designs, identify limitations and propose improvements based on testing outcomes.\nCommunication and collaboration: learners work in groups to plan, assign roles, make and present their project, communicating progress and challenges throughout.\nSelf-efficacy: learners demonstrate the confidence and persistence to carry a project from idea to finished product.",
    'values_attit'         => "Responsibility: learners take ownership of their project, meeting timelines and producing quality work.\nIntegrity: learners acknowledge the contributions of group members and present honest evaluations of their finished product.\nCare: learners handle tools and materials safely throughout the project and tidy the workspace after use.",
    'pcis'                 => "Innovation and creativity: learners apply the design process to produce innovative, locally-relevant products.\nCareer guidance: project work introduces learners to the professional cycle of design, production and evaluation used in technical careers.\nESD: learners design products that consider material economy, environment impact and community value.\nEntrepreneurship: learners consider the market potential and costing of their project product.",
    'links_to_other_areas' => 'Mathematics: learners apply measurement, scale, area and cost calculations to their project. Science: material properties and forces inform design and construction decisions. Business Studies/Entrepreneurship: costing and pricing of the project output.',
    'resources'            => "Materials appropriate to the project (wood, metal, plastic, reclaimed materials)\nHolding and driving tools studied in Strand 4\nMeasuring tools (ruler, tape measure, try-square)\nProject planning worksheet\nTextbook, chalkboard",
    'assessment'           => "Project portfolio/logbook: design brief, annotated sketch, materials list and process diary.\nFinished product evaluation: assess product against design criteria (functionality, finish, safety, environmental consideration).\nOral presentation: explain design decisions, challenges faced and how they were resolved.",
  ],

  // ── 5.0 ENTREPRENEURSHIP ─────────────────────────────────────────────────
  [
    'strand'               => '5.0 Entrepreneurship',
    'sub_strand'           => '5.1 Financial Services',
    'key_inquiry_qs'       => "What financial services are available to support small businesses?\nHow do we use financial services to grow a business?",
    'core_competencies'    => "Self-efficacy: learners develop confidence in managing personal finances and making informed decisions about savings, credit and investment.\nCritical thinking and problem solving: learners compare available financial services (bank accounts, mobile money, SACCOs, microfinance) and evaluate their suitability for different business needs.\nDigital literacy: learners explore mobile-banking and digital-payment platforms as business tools.",
    'values_attit'         => "Integrity: learners appreciate the importance of honest financial record-keeping, repaying loans and honouring financial commitments.\nResponsibility: learners demonstrate responsible borrowing and saving behaviour.\nSocial justice: learners discuss equitable access to financial services and the role of cooperative societies in empowering communities.",
    'pcis'                 => "Financial literacy: learners study savings, credit, insurance, mobile money and investment as tools for business growth.\nLife skills education: learners develop decision-making skills related to personal and business financial management.\nCareer guidance: learners explore careers in banking, microfinance and financial technology (FinTech).",
    'links_to_other_areas' => 'Mathematics: learners apply percentage, ratio and interest calculations to financial service scenarios. Social Studies: learners relate financial systems to the Kenyan economy studied in Social Studies.',
    'resources'            => "Charts showing types of financial institutions (banks, SACCOs, microfinance, mobile money)\nSample bank account opening forms and loan application forms\nMobile banking demonstration (teacher phone / video)\nTextbook, chalkboard",
    'assessment'           => "Case study analysis: recommend the most appropriate financial service for a described small business situation and justify the choice.\nOral questions: explain how a SACCO or bank account supports entrepreneurship.\nShort written test: calculate simple interest on a loan or savings scenario.",
  ],
  [
    'strand'               => '5.0 Entrepreneurship',
    'sub_strand'           => '5.2 Government and Business',
    'key_inquiry_qs'       => "What is the role of the government in business?\nHow do government policies and regulations affect entrepreneurs?",
    'core_competencies'    => "Critical thinking and problem solving: learners analyse the impact of government taxation, licensing and regulation on small businesses and propose how entrepreneurs can operate within the law.\nCitizenship: learners appreciate the contribution of taxes to public services and the importance of legal business registration.\nCommunication and collaboration: learners debate government roles in supporting and regulating business in group discussions.",
    'values_attit'         => "Patriotism: learners appreciate that taxes paid by businesses fund public services that benefit all Kenyans.\nIntegrity: learners commit to ethical business practices including accurate financial reporting, tax compliance and fair trade.\nResponsibility: learners understand the civic responsibility to register businesses and pay taxes as required by law.\nSocial justice: learners discuss how government regulation can protect consumers, employees and the environment.",
    'pcis'                 => "Citizenship education: learners study the legal and civic obligations of business operators in Kenya, including business licensing and tax compliance.\nFinancial literacy: learners understand the role of taxation, government levies and business facilitation funds.\nCareer guidance: learners explore careers in government regulatory bodies (KRA, KEBS, county business offices).",
    'links_to_other_areas' => 'Social Studies: learners relate government functions and devolution to business regulation at national and county level. Mathematics: learners apply percentage calculations when computing VAT, import duty and income tax.',
    'resources'            => "Charts: types of business registrations, licences and tax obligations in Kenya\nSample business permit, NSSF/NHIF registration forms (copies)\nKRA iTax awareness poster / video\nTextbook, chalkboard",
    'assessment'           => "Discussion task: explain two ways in which government policies support or constrain small businesses in Kenya.\nOral questions: describe the steps to legally register a small business and why registration matters.\nWritten exercise: list three government obligations of a small business owner and explain the consequence of non-compliance.",
  ],
  [
    'strand'               => '5.0 Entrepreneurship',
    'sub_strand'           => '5.3 Business Plan',
    'key_inquiry_qs'       => "What is a business plan?\nHow do we develop a business plan for a Pre-Technical Studies enterprise?",
    'core_competencies'    => "Critical thinking and problem solving: learners analyse market needs and competition, then design a viable business plan that addresses a real community opportunity.\nCreativity and imagination: learners develop innovative business ideas and create compelling, original business plans.\nSelf-efficacy: learners develop confidence in their entrepreneurial ability through the process of planning and presenting a business idea.\nCommunication and collaboration: learners work in groups to research, draft and pitch their business plan to peers or invited evaluators.",
    'values_attit'         => "Integrity: learners produce realistic, accurate financial projections and honestly assess the strengths and weaknesses of their business idea.\nResponsibility: learners take ownership of the planning process, meet deadlines and produce a well-organised plan.\nSocial justice: learners consider the social and environmental impact of their proposed business on the community.\nUnity: learners collaborate respectfully, valuing each team member's contribution to the business plan.",
    'pcis'                 => "Entrepreneurship education: learners apply the full entrepreneurial process — idea generation, market research, financial planning, marketing and evaluation — in developing their business plan.\nCareer guidance: business plan development prepares learners for self-employment and entrepreneurial careers in technical fields.\nESD: learners incorporate environmental sustainability considerations into their business plan.\nLife skills education: learners develop research, writing, financial literacy and presentation skills through the business planning process.",
    'links_to_other_areas' => 'Mathematics: learners apply cost calculations, profit/loss, break-even analysis and budgeting skills. English/Communication: learners use writing and presentation skills from Language lessons to prepare and pitch the plan.',
    'resources'            => "Business plan template / worksheet\nSample completed business plans (simple, appropriate level)\nCharts: components of a business plan (executive summary, market analysis, operations, financials, marketing)\nTextbook, chalkboard",
    'assessment'           => "Business plan document: evaluate completeness, realism and viability of the plan against a rubric (executive summary, market analysis, financial plan, marketing strategy).\nPitch presentation: assess clarity, confidence and ability to answer questions about the plan.\nPeer review: structured peer feedback on one another's business plans using a provided checklist.",
  ],
];

// ── Upsert ─────────────────────────────────────────────────────────────────
$sql = "INSERT INTO sub_strand_meta
        (learning_area_id, strand, sub_strand, key_inquiry_qs, core_competencies,
         values_attit, pcis, links_to_other_areas, resources, assessment)
        VALUES
        (:la, :strand, :ss, :ki, :cc, :val, :pcis, :links, :res, :ass)
        ON DUPLICATE KEY UPDATE
            key_inquiry_qs       = VALUES(key_inquiry_qs),
            core_competencies    = VALUES(core_competencies),
            values_attit         = VALUES(values_attit),
            pcis                 = VALUES(pcis),
            links_to_other_areas = VALUES(links_to_other_areas),
            resources            = VALUES(resources),
            assessment           = VALUES(assessment)";
$stmt = $pdo->prepare($sql);

$ok = 0; $err = 0;
foreach ($data as $row) {
    try {
        $stmt->execute([
            ':la'    => $laId,
            ':strand'=> $row['strand'],
            ':ss'    => $row['sub_strand'],
            ':ki'    => $row['key_inquiry_qs']       ?? '',
            ':cc'    => $row['core_competencies']    ?? '',
            ':val'   => $row['values_attit']         ?? '',
            ':pcis'  => $row['pcis']                 ?? '',
            ':links' => $row['links_to_other_areas'] ?? '',
            ':res'   => $row['resources']            ?? '',
            ':ass'   => $row['assessment']           ?? '',
        ]);
        $ok++;
    } catch (PDOException $e) {
        $err++;
        echo "<p style='color:red'>Error on [{$row['sub_strand']}]: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

$total = count($data);
echo "<style>body{font-family:Segoe UI,sans-serif;padding:30px}h2{color:#1a56db}.ok{color:#057a55;font-weight:bold}.warn{color:#b45309;font-weight:bold}a{color:#1a56db}</style>";
echo "<h2>Grade 9 Pre-Technical Studies — Curriculum Design Seed</h2>";
echo "<p class='ok'>&#10003; Seeded $ok / $total sub-strand records for <strong>{$la['grade_name']} — {$la['name']}</strong>.</p>";
if ($err) echo "<p class='warn'>&#9888; $err errors occurred (see details above).</p>";
echo "<p><a href='sub_strand_meta.php?la=$laId'>&#8594; View Curriculum Design data</a> &nbsp;&nbsp; <a href='index.php?learning_area_id=$laId'>&#8594; View Scheme of Work</a></p>";
