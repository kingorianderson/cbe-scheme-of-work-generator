<?php
// seed_pts.php — Seeds 88 lessons for Grade 9 Pre-Technical Studies
// Usage: http://localhost/SCHEME/seed_pts.php?la=1
// Add &force=1 to wipe existing rows first.
require_once 'config.php';
$pdo = getDB();

$learningAreaId = isset($_GET['la']) ? (int)$_GET['la'] : 1;

// Verify learning area
$laStmt = $pdo->prepare("SELECT la.*, g.name AS grade_name FROM learning_areas la JOIN grades g ON g.id = la.grade_id WHERE la.id = :id");
$laStmt->execute([':id' => $learningAreaId]);
$la = $laStmt->fetch();
if (!$la) {
    echo "<p style='color:red'>Learning area ID $learningAreaId not found. <a href='curriculum.php'>Go to Curriculum</a></p>"; exit;
}

$count = $pdo->prepare("SELECT COUNT(*) FROM scheme_of_work WHERE learning_area_id = :id");
$count->execute([':id' => $learningAreaId]);
$count = $count->fetchColumn();
if ($count > 0) {
    if (empty($_GET['force'])) {
        echo "<p style='color:orange;font-weight:bold'>Already has $count records. <a href='seed_pts.php?la=$learningAreaId&force=1' onclick=\"return confirm('Delete and re-seed?')\">Force re-seed</a> | <a href='index.php?learning_area_id=$learningAreaId'>View SOW</a></p>"; exit;
    }
    $pdo->prepare("DELETE FROM scheme_of_work WHERE learning_area_id = :id")->execute([':id' => $learningAreaId]);
}

$LESSONS_PER_WEEK = 4;

// ── Full 88-lesson data ─────────────────────────────────────────────────────
$lessons = [
  // ── 1.1 Safety on Raised Platforms ────────────────────────────────────────
  [
    'strand'      => '1.0 Foundations of Pre-Technical Studies',
    'sub_strand'  => '1.1 Safety on Raised Platforms',
    'slo_cd'      => 'a) identify types of raised platforms used in performing tasks',
    'slo_sow'     => "Lesson 1:\na) Define the term \"raised platforms.\"\nb) Identify various types of raised platforms used in performing tasks.\nc) Describe the characteristics of different raised platforms.",
    'le_cd'       => "• walk around the school to explore types of raised platforms (ladders, trestles, steps, stands, mobile raised platforms, work benches, ramps).\n• brainstorm on the types of raised platforms used in day-to-day life.",
    'le_sow'      => "• Define what is raised platforms thro question and answer.\n• Explore types of raised platforms, in groups, note down findings and share with the class.\n• Brainstorm on the types of raised platforms used in day-to-day life, in groups, and share findings with the rest of the class.",
    'key_inquiry' => 'What are raised platforms and where are they used?',
  ],
  [
    'strand'      => '1.0 Foundations of Pre-Technical Studies',
    'sub_strand'  => '1.1 Safety on Raised Platforms',
    'slo_cd'      => 'b) describe risks associated with working on raised platforms',
    'slo_sow'     => "Lesson 2:\na) Identify potential risks associated with working on raised platforms.\nb) Describe specific hazards encountered when working at different heights.",
    'le_cd'       => '• use print or digital media to search for information on risks associated with working on raised platforms.',
    'le_sow'      => '• Search for information on risk associated with working on raised platforms, in groups.  Write down findings and share them in class.',
    'key_inquiry' => 'What risks are associated with working on raised platforms?',
  ],
  [
    'strand'      => '1.0 Foundations of Pre-Technical Studies',
    'sub_strand'  => '1.1 Safety on Raised Platforms',
    'slo_cd'      => 'c) observe safety when working on raised platforms',
    'slo_sow'     => "Lesson 3:\na) Discuss effective methods of minimizing risks when working on raised platforms.\nb) Propose safety measures for preventing falls and injuries.",
    'le_cd'       => '• discuss ways of minimising risks related to working on raised platforms.',
    'le_sow'      => '• Discuss in groups, ways of minimising risks when working on raised platforms.',
    'key_inquiry' => 'How can we minimise risks when working on raised platforms?',
  ],
  [
    'strand'      => '1.0 Foundations of Pre-Technical Studies',
    'sub_strand'  => '1.1 Safety on Raised Platforms',
    'slo_cd'      => 'c) observe safety when working on raised platforms',
    'slo_sow'     => "Lesson 4:\na) Demonstrate safe practices for using raised platforms through role play.\nb) Critique safety behaviors observed during the role play session.",
    'le_cd'       => '• role-play safety practices for working on raised platforms.',
    'le_sow'      => '• Role play safety practices for working on raised platforms and share what they have learnt from each other in class.',
    'key_inquiry' => 'How do we demonstrate safe practices on raised platforms?',
  ],
  [
    'strand'      => '1.0 Foundations of Pre-Technical Studies',
    'sub_strand'  => '1.1 Safety on Raised Platforms',
    'slo_cd'      => 'd) appreciate the need for observing safety while working on raised platforms',
    'slo_sow'     => "Lesson 5:\na) Explain the significance of observing safety protocols while working at heights.\nb) Justify the use of Personal Protective Equipment (PPE) on raised platforms.",
    'le_cd'       => '• discuss the importance of observing safety when working on raised platforms.',
    'le_sow'      => '• Discuss in groups, the need for observing safety while working on raised platforms and share findings in class.',
    'key_inquiry' => 'Why is it important to observe safety on raised platforms?',
  ],
  [
    'strand'      => '1.0 Foundations of Pre-Technical Studies',
    'sub_strand'  => '1.1 Safety on Raised Platforms',
    'slo_cd'      => 'd) appreciate the need for observing safety while working on raised platforms',
    'slo_sow'     => "Lesson 6:\na) Evaluate personal understanding of safety on raised platforms through an assessment.",
    'le_cd'       => '• visit the locality to observe safety precautions taken when working on raised platforms.',
    'le_sow'      => '• Work individually on assessment exercise on safety on raised platforms.',
    'key_inquiry' => 'How well do I understand safety on raised platforms?',
  ],

  // ── 1.2 Handling Hazardous Substances ─────────────────────────────────────
  [
    'strand'      => '1.0 Foundations of Pre-Technical Studies',
    'sub_strand'  => '1.2 Handling Hazardous Substances',
    'slo_cd'      => 'a) identify hazardous substances found in the environment',
    'slo_sow'     => "Lesson 1:\na) Define the term \"hazardous substances.\"\nb) Identify common hazardous substances found in the school environment.\nc) Describe how specific local substances can cause harm.",
    'le_cd'       => "• use print or digital media to search for information on hazardous substances.\n• explore the environment to identify hazardous substances.",
    'le_sow'      => "• Define what are hazardous substances thro question and answer.\n• Identify hazardous substances found in the environments, in groups.\n• Mention hazardous substances found in the locality and how they cause harm.",
    'key_inquiry' => 'What are hazardous substances and where are they found?',
  ],
  [
    'strand'      => '1.0 Foundations of Pre-Technical Studies',
    'sub_strand'  => '1.2 Handling Hazardous Substances',
    'slo_cd'      => 'b) classify hazardous substances found in the locality',
    'slo_sow'     => "Lesson 2:\na) Categorize hazardous substances into poisonous, flammable, and corrosive groups.\nb) Distinguish between different classes of hazardous materials based on their properties.",
    'le_cd'       => '• group hazardous substances into poisonous, flammable or corrosive.',
    'le_sow'      => '• Group hazardous substances, in groups (Flammable, Corrosive and Poisonous substances).',
    'key_inquiry' => 'How are hazardous substances classified?',
  ],
  [
    'strand'      => '1.0 Foundations of Pre-Technical Studies',
    'sub_strand'  => '1.2 Handling Hazardous Substances',
    'slo_cd'      => 'c) describe safe ways of handling hazardous substances in the environment',
    'slo_sow'     => "Lesson 3:\na) Discuss safe methods for handling hazardous substances.\nb) Identify appropriate Personal Protective Equipment (PPE) for handling chemicals.",
    'le_cd'       => '• discuss safe ways of handling hazardous substances in the environment.',
    'le_sow'      => '• Discuss in groups, safe ways of handling hazardous substances and share findings with the class.',
    'key_inquiry' => 'How should we safely handle hazardous substances?',
  ],
  [
    'strand'      => '1.0 Foundations of Pre-Technical Studies',
    'sub_strand'  => '1.2 Handling Hazardous Substances',
    'slo_cd'      => 'c) describe safe ways of handling hazardous substances in the environment',
    'slo_sow'     => "Lesson 4:\na) Interpret safety instructions and conditions for use on chemical labels.\nb) Explain the meaning of various safety symbols found on hazardous containers.",
    'le_cd'       => '• read and interpret instructions on the conditions for use of hazardous substances.',
    'le_sow'      => '• Interpret, in groups, safety instructions on conditions for using hazardous substances and share findings with class.',
    'key_inquiry' => 'What do safety symbols on hazardous substances mean?',
  ],
  [
    'strand'      => '1.0 Foundations of Pre-Technical Studies',
    'sub_strand'  => '1.2 Handling Hazardous Substances',
    'slo_cd'      => 'd) handle hazardous substances safely in the environment',
    'slo_sow'     => "Lesson 5:\na) Demonstrate safe ways of handling hazardous substances through a simulated activity.\nb) Critique the handling techniques used by peers during the practice.",
    'le_cd'       => "• visit the locality to learn about safe handling of poisonous, flammable and corrosive substances.\n• practise safe handling of substances in the environment.",
    'le_sow'      => '• Practise, in groups, safe ways of handling hazardous substances and allow other learners to ask questions after the play.',
    'key_inquiry' => 'How do we demonstrate safe handling of hazardous substances?',
  ],
  [
    'strand'      => '1.0 Foundations of Pre-Technical Studies',
    'sub_strand'  => '1.2 Handling Hazardous Substances',
    'slo_cd'      => 'e) appreciate the importance of observing safety when handling hazardous substances',
    'slo_sow'     => "Lesson 6:\na) Explain the importance of following safety protocols when handling hazardous materials.\nb) Propose ways to promote safety awareness in the community.",
    'le_cd'       => '• discuss the importance of observing safety when handling hazardous substances.',
    'le_sow'      => '• Discuss in groups, the importance of observing safety when handling hazardous substances and present their findings.',
    'key_inquiry' => 'Why is it important to observe safety when handling hazardous substances?',
  ],
  [
    'strand'      => '1.0 Foundations of Pre-Technical Studies',
    'sub_strand'  => '1.2 Handling Hazardous Substances',
    'slo_cd'      => 'e) appreciate the importance of observing safety when handling hazardous substances',
    'slo_sow'     => "Lesson 7:\na) Evaluate personal proficiency in safety procedures through a structured assessment exercise.",
    'le_cd'       => '• visit the locality to learn about safe handling.',
    'le_sow'      => '• Work individually on assessment exercise on handling hazardous substances.',
    'key_inquiry' => 'How well can I handle hazardous substances safely?',
  ],

  // ── 1.3 Self-Exploration and Career Development ────────────────────────────
  [
    'strand'      => '1.0 Foundations of Pre-Technical Studies',
    'sub_strand'  => '1.3 Self-Exploration and Career Development',
    'slo_cd'      => 'a) explain ways of nurturing talents and abilities for self-development',
    'slo_sow'     => "Lesson 1:\na) Define the terms \"talents\" and \"abilities.\"\nb) Discuss various ways of nurturing talents and abilities.\nc) Outline how talent development contributes to self-growth.",
    'le_cd'       => '• discuss and present on ways of nurturing talents and abilities.',
    'le_sow'      => "• Discuss in groups, ways of nurturing talents and abilities.\n• Share findings with the class.",
    'key_inquiry' => 'How can we nurture our talents and abilities?',
  ],
  [
    'strand'      => '1.0 Foundations of Pre-Technical Studies',
    'sub_strand'  => '1.3 Self-Exploration and Career Development',
    'slo_cd'      => 'b) relate talents and abilities to career pathways',
    'slo_sow'     => "Lesson 2:\na) Identify different career pathways available in Senior School.\nb) Relate specific talents and abilities to their corresponding career pathways.",
    'le_cd'       => '• make a list of talents and abilities and their corresponding career pathways.',
    'le_sow'      => "• Relate, in groups, talents and abilities to career pathways.\n• Share talent and ability in class and mention the career pathway.",
    'key_inquiry' => 'How do talents and abilities relate to career pathways?',
  ],
  [
    'strand'      => '1.0 Foundations of Pre-Technical Studies',
    'sub_strand'  => '1.3 Self-Exploration and Career Development',
    'slo_cd'      => 'b) relate talents and abilities to career pathways',
    'slo_sow'     => "Lesson 3:\na) List various career opportunities related to specific talents.\nb) Evaluate information from a resource person regarding career progression.",
    'le_cd'       => '• engage with a resource person on career opportunities related to talents and abilities.',
    'le_sow'      => "• Make a list of talents and corresponding career pathways.\n• Engage with a resource person (guest, video, or article) on career opportunities.",
    'key_inquiry' => 'What career opportunities are linked to my talents?',
  ],
  [
    'strand'      => '1.0 Foundations of Pre-Technical Studies',
    'sub_strand'  => '1.3 Self-Exploration and Career Development',
    'slo_cd'      => 'c) analyse ethical and unethical practices related to the use of talents and abilities',
    'slo_sow'     => "Lesson 4:\na) Analyze case studies on the use of talents and abilities.\nb) Distinguish between ethical and unethical practices in the application of talents.",
    'le_cd'       => '• discuss a case scenario on ethical and unethical practices related to the use of talents and abilities.',
    'le_sow'      => '• Discuss, in groups, case studies on ethical and unethical practices related to the use of talents and abilities and share with the class.',
    'key_inquiry' => 'What are ethical and unethical uses of talents?',
  ],
  [
    'strand'      => '1.0 Foundations of Pre-Technical Studies',
    'sub_strand'  => '1.3 Self-Exploration and Career Development',
    'slo_cd'      => 'd) choose a career based on talents and abilities for self-development',
    'slo_sow'     => "Lesson 5:\na) Select a preferred career based on personal talents and abilities.\nb) Explain the reasons for the chosen career in relation to self-development.",
    'le_cd'       => '• make presentations on careers of choice based on talents and abilities.',
    'le_sow'      => "• Choose, in groups, careers based on talents and abilities.\n• Share chosen careers with the class.",
    'key_inquiry' => 'Which career best suits my talents and abilities?',
  ],
  [
    'strand'      => '1.0 Foundations of Pre-Technical Studies',
    'sub_strand'  => '1.3 Self-Exploration and Career Development',
    'slo_cd'      => 'd) choose a career based on talents and abilities for self-development',
    'slo_sow'     => "Lesson 6:\na) Evaluate personal career goals through a self-exploration assessment exercise.",
    'le_cd'       => '• make presentations on careers of choice.',
    'le_sow'      => '• Work individually on assessment exercise on self-exploration and career development.',
    'key_inquiry' => 'How well do I understand my career path?',
  ],

  // ── 2.1 Oblique Projection ─────────────────────────────────────────────────
  [
    'strand'      => '2.0 Communication in Pre-Technical Studies',
    'sub_strand'  => '2.1 Oblique Projection',
    'slo_cd'      => 'a) explain the characteristics of oblique drawing in technical fields',
    'slo_sow'     => "Lesson 1:\na) Define the term \"oblique projection.\"\nb) Identify different types of oblique drawings (Cavalier and Cabinet).\nc) Explain the use of oblique drawings in technical fields.",
    'le_cd'       => '• use print or digital media to search for information on the characteristic of oblique drawings.',
    'le_sow'      => "• Search for information on meaning of oblique projection, examples of oblique drawings, and characteristics.\n• Share findings with the class.",
    'key_inquiry' => 'What is oblique projection and where is it used?',
  ],
  [
    'strand'      => '2.0 Communication in Pre-Technical Studies',
    'sub_strand'  => '2.1 Oblique Projection',
    'slo_cd'      => 'a) explain the characteristics of oblique drawing in technical fields',
    'slo_sow'     => "Lesson 2:\na) Describe the key characteristics of oblique drawings (sloping axes and front face).\nb) Distinguish between oblique and other pictorial drawings.",
    'le_cd'       => '• brainstorm on the characteristic of oblique drawings.',
    'le_sow'      => '• Brainstorm, in groups, on the characteristics of oblique drawings and share with the class.',
    'key_inquiry' => 'What are the characteristics of oblique drawings?',
  ],
  [
    'strand'      => '2.0 Communication in Pre-Technical Studies',
    'sub_strand'  => '2.1 Oblique Projection',
    'slo_cd'      => 'b) sketch given drawings in oblique projection',
    'slo_sow'     => "Lesson 3:\na) Identify the starting point for sketching an oblique drawing.\nb) Sketch simple shaped blocks in oblique projection using freehand.",
    'le_cd'       => '• draw given drawings in oblique projection without using instruments.',
    'le_sow'      => '• Sketch drawings in oblique projection without using instruments.',
    'key_inquiry' => 'How do we sketch simple shapes in oblique projection freehand?',
  ],
  [
    'strand'      => '2.0 Communication in Pre-Technical Studies',
    'sub_strand'  => '2.1 Oblique Projection',
    'slo_cd'      => 'b) sketch given drawings in oblique projection',
    'slo_sow'     => "Lesson 4:\na) Refine freehand oblique sketches for proportion and clarity.\nb) Sketch complex shaped blocks in oblique projection.",
    'le_cd'       => '• draw given drawings in oblique projection without using instruments.',
    'le_sow'      => '• Sketch more complex shaped blocks in oblique projection without using instruments.',
    'key_inquiry' => 'How do we sketch complex shapes in oblique projection?',
  ],
  [
    'strand'      => '2.0 Communication in Pre-Technical Studies',
    'sub_strand'  => '2.1 Oblique Projection',
    'slo_cd'      => 'c) draw shaped blocks in oblique projection',
    'slo_sow'     => "Lesson 5:\na) Outline the step-by-step procedure for drawing oblique blocks.\nb) Discuss the importance of using a 45-degree set square in oblique drawing.",
    'le_cd'       => '• discuss the steps for drawing shaped blocks in oblique projection.',
    'le_sow'      => '• Discuss, in pairs, the steps for drawing shaped blocks in oblique projection.',
    'key_inquiry' => 'What are the steps for drawing shaped blocks in oblique projection?',
  ],
  [
    'strand'      => '2.0 Communication in Pre-Technical Studies',
    'sub_strand'  => '2.1 Oblique Projection',
    'slo_cd'      => 'c) draw shaped blocks in oblique projection',
    'slo_sow'     => "Lesson 6:\na) Construct accurate oblique drawings of shaped blocks using a geometrical set.\nb) Demonstrate the correct use of drawing instruments for cabinet projection.",
    'le_cd'       => '• use geometrical set drawing instruments to draw shaped blocks in oblique projection (cabinet).',
    'le_sow'      => '• Draw, in pairs, shaped blocks in oblique projection using drawing instruments.',
    'key_inquiry' => 'How do we draw shaped blocks accurately using instruments?',
  ],
  [
    'strand'      => '2.0 Communication in Pre-Technical Studies',
    'sub_strand'  => '2.1 Oblique Projection',
    'slo_cd'      => 'c) draw shaped blocks in oblique projection',
    'slo_sow'     => "Lesson 7:\na) Construct individual figures in oblique projection with precision.\nb) Apply line types correctly (outlines and construction lines) in oblique drawings.",
    'le_cd'       => '• use geometrical set drawing instruments to draw shaped blocks.',
    'le_sow'      => '• Work individually, using appropriate drawing instruments, draw figures in oblique projection.',
    'key_inquiry' => 'How do I independently draw figures in oblique projection?',
  ],
  [
    'strand'      => '2.0 Communication in Pre-Technical Studies',
    'sub_strand'  => '2.1 Oblique Projection',
    'slo_cd'      => 'd) appreciate the application of oblique projection on drawing',
    'slo_sow'     => "Lesson 8:\na) Analyze how oblique projection is applied in technical communication.\nb) Discuss the advantages of using oblique projection over other drawing methods.",
    'le_cd'       => '• walk around the locality to observe the use of oblique drawings.',
    'le_sow'      => '• Discuss the application of oblique projection in technical communication and present findings.',
    'key_inquiry' => 'How is oblique projection applied in real life?',
  ],
  [
    'strand'      => '2.0 Communication in Pre-Technical Studies',
    'sub_strand'  => '2.1 Oblique Projection',
    'slo_cd'      => 'd) appreciate the application of oblique projection on drawing',
    'slo_sow'     => "Lesson 9:\na) Evaluate personal drawing skills through a practical assessment exercise.",
    'le_cd'       => '• walk around the locality to observe the use of oblique drawings.',
    'le_sow'      => '• Work individually on assessment exercise on oblique projection.',
    'key_inquiry' => 'How well can I draw in oblique projection?',
  ],

  // ── 2.2 Visual Programming ─────────────────────────────────────────────────
  [
    'strand'      => '2.0 Communication in Pre-Technical Studies',
    'sub_strand'  => '2.2 Visual Programming',
    'slo_cd'      => 'a) explain the application areas of visual programming software in solving problems',
    'slo_sow'     => "Lesson 1:\na) Define the term \"visual programming.\"\nb) Identify various application areas of visual programming in problem-solving.\nc) Outline the benefits of visual programming over text-based coding.",
    'le_cd'       => '• use print or digital media to search for information on the application areas of visual programming.',
    'le_sow'      => "• Search for information on application area of visual programming.\n• Write short notes and give examples of websites and mobile applications.",
    'key_inquiry' => 'What is visual programming and where is it applied?',
  ],
  [
    'strand'      => '2.0 Communication in Pre-Technical Studies',
    'sub_strand'  => '2.2 Visual Programming',
    'slo_cd'      => 'a) explain the application areas of visual programming software in solving problems',
    'slo_sow'     => "Lesson 2:\na) Identify examples of mobile applications developed using visual programming.\nb) Discuss how web development utilizes visual programming tools.",
    'le_cd'       => '• discuss the application areas of visual programming software.',
    'le_sow'      => '• Discuss, in groups, the application areas in visual programming - Mobile programming and Web development.',
    'key_inquiry' => 'How is visual programming used in mobile and web development?',
  ],
  [
    'strand'      => '2.0 Communication in Pre-Technical Studies',
    'sub_strand'  => '2.2 Visual Programming',
    'slo_cd'      => 'b) create an application using visual programming software',
    'slo_sow'     => "Lesson 3:\na) Demonstrate how to download and install Scratch on a digital device.\nb) Identify the system requirements for running visual programming software.",
    'le_cd'       => '• watch a video on how to develop an application using visual programming software.',
    'le_sow'      => "• How to download and install Scratch in a device.\n• Watch a video of a resource person.",
    'key_inquiry' => 'How do we install and set up Scratch?',
  ],
  [
    'strand'      => '2.0 Communication in Pre-Technical Studies',
    'sub_strand'  => '2.2 Visual Programming',
    'slo_cd'      => 'b) create an application using visual programming software',
    'slo_sow'     => "Lesson 4:\na) Identify the components of the Scratch user interface (Stage, Sprites, Blocks Palette).\nb) Describe the functions of different interface areas.",
    'le_cd'       => '• develop interactive stories, games and animations using visual programming software.',
    'le_sow'      => "• Familiarise yourself with Scratch programming, in groups.\n• Explore the Scratch interface.",
    'key_inquiry' => 'What are the components of the Scratch interface?',
  ],
  [
    'strand'      => '2.0 Communication in Pre-Technical Studies',
    'sub_strand'  => '2.2 Visual Programming',
    'slo_cd'      => 'b) create an application using visual programming software',
    'slo_sow'     => "Lesson 5:\na) Categorize Scratch blocks based on their colors and functions (Motion, Events, Control).\nb) Demonstrate how to snap blocks together to form a script.",
    'le_cd'       => '• develop interactive stories, games and animations using visual programming software.',
    'le_sow'      => '• Familiarise yourself with Scratch programming environment.',
    'key_inquiry' => 'How do Scratch blocks work together?',
  ],
  [
    'strand'      => '2.0 Communication in Pre-Technical Studies',
    'sub_strand'  => '2.2 Visual Programming',
    'slo_cd'      => 'b) create an application using visual programming software',
    'slo_sow'     => "Lesson 6:\na) Formulate a logical sequence of steps (algorithm) for a simple game.\nb) Write down the planned steps for a personal game project.",
    'le_cd'       => '• develop interactive stories, games and animations using visual programming software.',
    'le_sow'      => '• Write down the steps to follow to develop your game.',
    'key_inquiry' => 'How do we plan an algorithm for a game?',
  ],
  [
    'strand'      => '2.0 Communication in Pre-Technical Studies',
    'sub_strand'  => '2.2 Visual Programming',
    'slo_cd'      => 'b) create an application using visual programming software',
    'slo_sow'     => "Lesson 7:\na) Construct a simple interactive game using Motion and Events blocks.\nb) Test and play the game to ensure functionality.",
    'le_cd'       => '• develop interactive stories, games and animations using visual programming software.',
    'le_sow'      => "• Create and play a fun game, following steps.\n• Develop, in groups, interactive games using Scratch.",
    'key_inquiry' => 'How do we create an interactive game in Scratch?',
  ],
  [
    'strand'      => '2.0 Communication in Pre-Technical Studies',
    'sub_strand'  => '2.2 Visual Programming',
    'slo_cd'      => 'b) create an application using visual programming software',
    'slo_sow'     => "Lesson 8:\na) Construct an interactive story using conversation (Say) blocks and multiple backdrops.\nb) Demonstrate the story to peers for feedback.",
    'le_cd'       => '• develop interactive stories, games and animations using visual programming software.',
    'le_sow'      => "• Write down steps to follow to develop the story.\n• Develop interactive stories using Scratch.",
    'key_inquiry' => 'How do we create an interactive story in Scratch?',
  ],
  [
    'strand'      => '2.0 Communication in Pre-Technical Studies',
    'sub_strand'  => '2.2 Visual Programming',
    'slo_cd'      => 'b) create an application using visual programming software',
    'slo_sow'     => "Lesson 9:\na) Apply \"Costumes\" and \"Sound\" blocks to create a simple animation.\nb) Demonstrate the finished animation to the class.",
    'le_cd'       => '• develop interactive stories, games and animations using visual programming software.',
    'le_sow'      => "• Develop, in groups, animations using visual programming.\n• Play the animation for the class to see.",
    'key_inquiry' => 'How do we create an animation in Scratch?',
  ],
  [
    'strand'      => '2.0 Communication in Pre-Technical Studies',
    'sub_strand'  => '2.2 Visual Programming',
    'slo_cd'      => 'c) embrace the use of visual programming in the day-to-day life',
    'slo_sow'     => "Lesson 10:\na) Discuss how visual programming can be used to solve real-life challenges.\nb) Evaluate personal understanding of programming through an assessment exercise.",
    'le_cd'       => '• practise using visual programming applications to solve problems in day-to-day life.',
    'le_sow'      => "• Discuss the uses of visual programming.\n• Work individually on assessment exercise on visual programming.",
    'key_inquiry' => 'How is visual programming useful in everyday life?',
  ],

  // ── 3.1 Wood ───────────────────────────────────────────────────────────────
  [
    'strand'      => '3.0 Materials for Production',
    'sub_strand'  => '3.1 Wood',
    'slo_cd'      => 'a) classify wood according to physical characteristics',
    'slo_sow'     => "Lesson 1:\na) Identify available types of wood in the local environment.\nb) Describe the physical properties of different wood types.\nc) Distinguish between softwood and hardwood.",
    'le_cd'       => "• use print or digital media to search for information on types of wood.\n• discuss the physical characteristics of wood (soft and hard wood).\n• use a checklist to sort wood as either softwood or hardwood.",
    'le_sow'      => "• Search for information on types of wood available, physical property of each type of wood and example of each type of wood.\n• Share findings with the class.\n• Walk around the school or community and identify the trees in the environment while observing physical characteristics.",
    'key_inquiry' => 'How do we classify wood based on physical characteristics?',
  ],
  [
    'strand'      => '3.0 Materials for Production',
    'sub_strand'  => '3.1 Wood',
    'slo_cd'      => 'b) describe the preparation of wood for use in the production of items',
    'slo_sow'     => "Lesson 2:\na) Describe the process of wood conversion.\nb) Illustrate different wood conversion methods through drawing.",
    'le_cd'       => '• discuss methods of wood preparation (conversion and seasoning).',
    'le_sow'      => "• Discuss, in groups, methods of wood preparation - Conversion.\n• Draw images, individually, to represent each conversion method.",
    'key_inquiry' => 'How is wood converted for production use?',
  ],
  [
    'strand'      => '3.0 Materials for Production',
    'sub_strand'  => '3.1 Wood',
    'slo_cd'      => 'b) describe the preparation of wood for use in the production of items',
    'slo_sow'     => "Lesson 3:\na) Describe the process of wood seasoning.\nb) Explain the importance of seasoning wood before production.",
    'le_cd'       => '• discuss methods of wood preparation (conversion and seasoning).',
    'le_sow'      => "• Discuss, in groups, methods of wood preparation - Seasoning.\n• Share findings with the class.",
    'key_inquiry' => 'Why is wood seasoning important before production?',
  ],
  [
    'strand'      => '3.0 Materials for Production',
    'sub_strand'  => '3.1 Wood',
    'slo_cd'      => 'c) relate types of wood to their uses in the community',
    'slo_sow'     => "Lesson 4:\na) Identify the uses of specific wood types in the community.\nb) Relate various wood types to specific trades and industries.",
    'le_cd'       => "• brainstorm on the uses of wood in different trades.\n• develop charts to match types of wood to their uses.",
    'le_sow'      => "• Brainstorm on the uses of specific wood, in groups.\n• Brainstorm on the uses of wood in various trades and share findings.",
    'key_inquiry' => 'How are different types of wood used in the community?',
  ],
  [
    'strand'      => '3.0 Materials for Production',
    'sub_strand'  => '3.1 Wood',
    'slo_cd'      => 'd) value the importance of wood in day-to-day life',
    'slo_sow'     => "Lesson 5:\na) Explain the socio-economic importance of wood in daily life.\nb) Evaluate the importance of wood conservation.",
    'le_cd'       => '• discuss the importance of wood in day-to-day life.',
    'le_sow'      => '• Discuss, in groups, the importance of wood in day-to-day life.',
    'key_inquiry' => 'Why is wood important in our daily lives?',
  ],
  [
    'strand'      => '3.0 Materials for Production',
    'sub_strand'  => '3.1 Wood',
    'slo_cd'      => 'd) value the importance of wood in day-to-day life',
    'slo_sow'     => "Lesson 6:\na) Assess personal understanding of wood properties and preparation through an exercise.",
    'le_cd'       => '• visit the locality to explore the uses of wood.',
    'le_sow'      => '• Work individually on assessment exercise on wood.',
    'key_inquiry' => 'How well do I understand the properties and uses of wood?',
  ],

  // ── 3.2 Handling Waste Materials ──────────────────────────────────────────
  [
    'strand'      => '3.0 Materials for Production',
    'sub_strand'  => '3.2 Handling Waste Materials',
    'slo_cd'      => 'a) identify waste materials found in the environment',
    'slo_sow'     => "Lesson 1:\na) Identify different types of waste materials in the school environment.\nb) List the identified waste materials in categories.",
    'le_cd'       => '• walk around the school compound to identify waste materials (plastic, glass, metal, wood waste, electronic waste, construction waste).',
    'le_sow'      => "• Walk around the school compound, in groups, to identify waste materials.\n• Write the waste materials you see in exercise book and read the list in class.",
    'key_inquiry' => 'What types of waste materials exist in our environment?',
  ],
  [
    'strand'      => '3.0 Materials for Production',
    'sub_strand'  => '3.2 Handling Waste Materials',
    'slo_cd'      => 'b) describe ways of handling waste materials safely in the environment',
    'slo_sow'     => "Lesson 2:\na) Identify safe ways of handling different waste materials.\nb) Describe the methods of reducing, reusing, and recycling waste.",
    'le_cd'       => "• use print or digital media to search for information on safe ways of handling waste materials.\n• discuss safe ways of handling waste materials (reduce, reuse, recycle, compost and burn).",
    'le_sow'      => '• Search for information on safe handling of waste materials and discuss findings in class.',
    'key_inquiry' => 'How do we safely handle waste materials using the 3Rs?',
  ],

  // ── 4.1 Holding Tools ──────────────────────────────────────────────────────
  [
    'strand'      => '4.0 Tools and Production',
    'sub_strand'  => '4.1 Holding Tools',
    'slo_cd'      => 'a) identify holding tools used in day-to-day life',
    'slo_sow'     => "Lesson 1:\na) Identify common holding tools used in the environment.\nb) Illustrate various holding tools through drawing.\nc) Label the parts of identified holding tools.",
    'le_cd'       => '• use visual aids or real objects to identify holding tools.',
    'le_sow'      => "• Identify, in groups, the pictures of holding tools in the environment.\n• Draw and name the identified tools.",
    'key_inquiry' => 'What are the common holding tools used in daily life?',
  ],
  [
    'strand'      => '4.0 Tools and Production',
    'sub_strand'  => '4.1 Holding Tools',
    'slo_cd'      => 'b) select holding tools for performing given tasks',
    'slo_sow'     => "Lesson 2:\na) Choose the most appropriate holding tool for a specific task.\nb) Justify the selection of a holding tool based on the nature of the work.",
    'le_cd'       => "• choose holding tools for different tasks.\n• discuss uses of different types of holding tools (pliers, clamps, tongs, clips, vice).",
    'le_sow'      => "• Choose, in groups, holding tools for different tasks.\n• Select the most appropriate tools for given tasks and give reasons for the choice.",
    'key_inquiry' => 'How do we select the right holding tool for a task?',
  ],
  [
    'strand'      => '4.0 Tools and Production',
    'sub_strand'  => '4.1 Holding Tools',
    'slo_cd'      => 'c) use holding tools to perform given tasks',
    'slo_sow'     => "Lesson 3:\na) Describe tasks being performed using holding tools from visual aids.\nb) Outline safe procedures for using pliers, clamps, and vices.",
    'le_cd'       => "• use print or digital media to search for information on safe use of holding tools.\n• demonstrate safe use of holding tools when performing different types of tasks.",
    'le_sow'      => "• Study pictures, in groups, of people using different holding tools and describe the tasks.\n• Search for information or watch a video on how to safely use holding tools.",
    'key_inquiry' => 'How do we use holding tools safely?',
  ],
  [
    'strand'      => '4.0 Tools and Production',
    'sub_strand'  => '4.1 Holding Tools',
    'slo_cd'      => 'c) use holding tools to perform given tasks',
    'slo_sow'     => "Lesson 4:\na) Demonstrate the correct use of a specific holding tool to perform a practical task.\nb) Apply safety measures while using holding tools in the environment.",
    'le_cd'       => '• demonstrate safe use of holding tools.',
    'le_sow'      => "• Identify suitable tasks in the environment that require holding tools.\n• Perform the identified task using the most appropriate holding tool.",
    'key_inquiry' => 'How do we apply holding tools safely in a practical task?',
  ],
  [
    'strand'      => '4.0 Tools and Production',
    'sub_strand'  => '4.1 Holding Tools',
    'slo_cd'      => 'd) care for holding tools used in day-to-day life',
    'slo_sow'     => "Lesson 5:\na) Describe methods for cleaning and maintaining holding tools.\nb) Demonstrate proper storage of holding tools to prevent damage.",
    'le_cd'       => '• clean and safely store holding tools.',
    'le_sow'      => "• Study and discuss pictures or videos on how to care for and maintain tools.\n• Share findings with the class on maintenance practices.",
    'key_inquiry' => 'How do we care for and store holding tools?',
  ],
  [
    'strand'      => '4.0 Tools and Production',
    'sub_strand'  => '4.1 Holding Tools',
    'slo_cd'      => 'e) appreciate the importance of holding tools in day-to-day life',
    'slo_sow'     => "Lesson 6:\na) Explain the significance of holding tools in community trades.\nb) Evaluate personal proficiency in using holding tools through an exercise.",
    'le_cd'       => '• share experiences on the use of holding tools.',
    'le_sow'      => "• Discuss, in groups, the importance of holding tools in the community.\n• Share experiences and work individually on assessment exercise.",
    'key_inquiry' => 'Why are holding tools important in the community?',
  ],

  // ── 4.2 Driving Tools ──────────────────────────────────────────────────────
  [
    'strand'      => '4.0 Tools and Production',
    'sub_strand'  => '4.2 Driving Tools',
    'slo_cd'      => 'a) identify driving tools used in day-to-day life',
    'slo_sow'     => "Lesson 1:\na) Identify pictures of driving tools used in the community.\nb) State where various driving tools are commonly found.",
    'le_cd'       => '• use visual aids or realia to identify driving tools in the community.',
    'le_sow'      => "• Identify, in groups, pictures of driving tools used in the community.\n• Mention where these tools can be found within the locality.",
    'key_inquiry' => 'What driving tools are used in everyday life?',
  ],
  [
    'strand'      => '4.0 Tools and Production',
    'sub_strand'  => '4.2 Driving Tools',
    'slo_cd'      => 'b) select driving tools for performing given tasks',
    'slo_sow'     => "Lesson 2:\na) Match driving tools to specific tasks on flashcards.\nb) Defend the choice of a specific driving tool for a given task.",
    'le_cd'       => '• discuss uses of driving tools for different tasks (hammer, screwdriver, spanner, punches, mallets).',
    'le_sow'      => "• Select the most appropriate driving tools for tasks shown on flashcards.\n• Give reasons for the choice of tool selected in each case.",
    'key_inquiry' => 'How do we select the right driving tool for a task?',
  ],
  [
    'strand'      => '4.0 Tools and Production',
    'sub_strand'  => '4.2 Driving Tools',
    'slo_cd'      => 'c) use driving tools to perform given tasks',
    'slo_sow'     => "Lesson 3:\na) Identify tasks observed in the community that require driving tools.\nb) Demonstrate the safe use of a driving tool to perform a specific task.",
    'le_cd'       => "• use print or digital devices to search and watch a video clip on the safe use of driving tools.\n• demonstrate safe use of driving tools.",
    'le_sow'      => "• Discuss pictures of driving tools in use and mention tasks observed in the community.\n• Identify and perform a task in the environment using the appropriate driving tool.",
    'key_inquiry' => 'How do we use driving tools safely to perform tasks?',
  ],
  [
    'strand'      => '4.0 Tools and Production',
    'sub_strand'  => '4.2 Driving Tools',
    'slo_cd'      => 'd) care for driving tools used in day-to-day',
    'slo_sow'     => "Lesson 4:\na) Describe the procedures for maintaining driving tools (e.g., oiling, tightening).\nb) Demonstrate safe storage practices for driving tools.",
    'le_cd'       => '• clean and safely store driving tools.',
    'le_sow'      => "• Study and discuss pictures on how to care for and maintain driving tools.\n• Answer questions regarding maintenance and storage.",
    'key_inquiry' => 'How do we maintain and store driving tools?',
  ],
  [
    'strand'      => '4.0 Tools and Production',
    'sub_strand'  => '4.2 Driving Tools',
    'slo_cd'      => 'e) acknowledge the importance of driving tools in day-to-day life',
    'slo_sow'     => "Lesson 5:\na) Explain the necessity of driving tools in daily activities.\nb) Assess understanding of driving tools through an individual exercise.",
    'le_cd'       => '• share experiences on the use of driving tools.',
    'le_sow'      => "• Discuss, in groups, the need for driving tools in the community.\n• Work individually on assessment exercise on driving tools.",
    'key_inquiry' => 'Why are driving tools important in daily life?',
  ],

  // ── 4.3 Project (20 lessons) ───────────────────────────────────────────────
  [
    'strand'     => '4.0 Tools and Production', 'sub_strand' => '4.3 Project',
    'slo_cd'     => 'a) identify a problem in the locality that can be solved using the skills acquired in Pre-Technical Studies',
    'slo_sow'    => "Lesson 1:\na) Identify existing challenges within the local community.\nb) Establish problems that can be solved using technical skills.",
    'le_cd'      => '• explore the locality to identify problems that can be solved using the skills acquired in Pre-Technical Studies.',
    'le_sow'     => "• Explore the locality to establish problems to be solved.\n• Present the identified problem findings in class.",
    'key_inquiry'=> 'What problems in our locality can be solved with Pre-Technical skills?',
  ],
  [
    'strand'     => '4.0 Tools and Production', 'sub_strand' => '4.3 Project',
    'slo_cd'     => 'a) identify a problem in the locality',
    'slo_sow'    => "Lesson 2:\na) Analyze identified problems in the locality.\nb) Select the most suitable problem to address using available resources.",
    'le_cd'      => '• brainstorm on the possible solutions to the identified problems.',
    'le_sow'     => '• Brainstorm on the problems in the locality which can be solved using skills acquired.',
    'key_inquiry'=> 'Which identified problem is most suitable for a project?',
  ],
  [
    'strand'     => '4.0 Tools and Production', 'sub_strand' => '4.3 Project',
    'slo_cd'     => 'b) select an item that can be made to solve the identified problem',
    'slo_sow'    => "Lesson 3:\na) Propose various handmade or digital items to solve the problem.\nb) Identify the technical skills needed to make the selected item.",
    'le_cd'      => "• search for information on possible items to solve the identified problem.\n• discuss possible items that can be made.",
    'le_sow'     => "• Search for information on possible items to solve the identified problem.\n• Suggest the technical skills needed.",
    'key_inquiry'=> 'What item can be made to solve the identified problem?',
  ],
  [
    'strand'     => '4.0 Tools and Production', 'sub_strand' => '4.3 Project',
    'slo_cd'     => 'c) make an item to solve the problem identified using locally available materials',
    'slo_sow'    => "Lesson 4:\na) Create freehand sketches of the selected project item.\nb) Illustrate the features and dimensions of the item.",
    'le_cd'      => '• sketch the item to be made using the skills acquired.',
    'le_sow'     => '• Make freehand sketches of the item selected.',
    'key_inquiry'=> 'How do we sketch the project item?',
  ],
  [
    'strand'     => '4.0 Tools and Production', 'sub_strand' => '4.3 Project',
    'slo_cd'     => 'c) make an item to solve the problem identified',
    'slo_sow'    => "Lesson 5:\na) Formulate the logical steps followed when making the item.\nb) Note down the instructional steps for production.",
    'le_cd'      => '• discuss possible items that can be made to solve the identified problem.',
    'le_sow'     => '• Find out and note down the steps followed when making the item.',
    'key_inquiry'=> 'What steps are followed to make the project item?',
  ],
  [
    'strand'     => '4.0 Tools and Production', 'sub_strand' => '4.3 Project',
    'slo_cd'     => 'c) make an item to solve the problem identified',
    'slo_sow'    => "Lesson 6:\na) List the specific materials and tools required for the project.\nb) Identify locally available materials for the project.",
    'le_cd'      => '• use locally available materials and tools to make the identified item.',
    'le_sow'     => '• Identify materials and tools for making the selected items.',
    'key_inquiry'=> 'What materials and tools are needed for the project?',
  ],
  [
    'strand'     => '4.0 Tools and Production', 'sub_strand' => '4.3 Project',
    'slo_cd'     => 'c) make an item to solve the problem identified',
    'slo_sow'    => "Lesson 7:\na) Estimate the cost of materials for making the project item.\nb) Calculate the total expenditure for the project.",
    'le_cd'      => '• estimating the cost of materials for making the project item.',
    'le_sow'     => '• Estimate, in groups, the cost incurred to make the item.',
    'key_inquiry'=> 'How much will the project cost to make?',
  ],
  [
    'strand'     => '4.0 Tools and Production', 'sub_strand' => '4.3 Project',
    'slo_cd'     => 'c) make an item to solve the problem identified',
    'slo_sow'    => "Lesson 8:\na) Determine a suitable selling price for the finished item.\nb) Justify the price based on production costs and value.",
    'le_cd'      => '• estimate the cost to determine the price for the item.',
    'le_sow'     => '• Determine the price for the item based on estimated costs.',
    'key_inquiry'=> 'How do we determine a fair price for the finished item?',
  ],
  [
    'strand'     => '4.0 Tools and Production', 'sub_strand' => '4.3 Project',
    'slo_cd'     => 'c) make an item to solve the problem identified',
    'slo_sow'    => "Lesson 9:\na) Construct the project item using locally available materials.\nb) Apply safety measures during the production process.",
    'le_cd'      => '• use locally available materials and tools to make the identified item.',
    'le_sow'     => '• Make the selected item using the sketch made while following instructional steps.',
    'key_inquiry'=> 'How do we construct the project item safely?',
  ],
  [
    'strand'     => '4.0 Tools and Production', 'sub_strand' => '4.3 Project',
    'slo_cd'     => 'c) make an item to solve the problem identified',
    'slo_sow'    => "Lesson 10:\na) Construct the project item using locally available materials.\nb) Apply safety measures during the production process.",
    'le_cd'      => '• use locally available materials and tools to make the identified item.',
    'le_sow'     => '• Make the selected item using the sketch made while following instructional steps.',
    'key_inquiry'=> 'How do we continue constructing the project item safely?',
  ],
  [
    'strand'     => '4.0 Tools and Production', 'sub_strand' => '4.3 Project',
    'slo_cd'     => 'c) make an item to solve the problem identified',
    'slo_sow'    => "Lesson 11:\na) Construct the project item using locally available materials.\nb) Apply safety measures during the production process.",
    'le_cd'      => '• use locally available materials and tools to make the identified item.',
    'le_sow'     => '• Make the selected item using the sketch made while following instructional steps.',
    'key_inquiry'=> 'How do we ensure quality during project construction?',
  ],
  [
    'strand'     => '4.0 Tools and Production', 'sub_strand' => '4.3 Project',
    'slo_cd'     => 'c) make an item to solve the problem identified',
    'slo_sow'    => "Lesson 12:\na) Construct the project item using locally available materials.\nb) Apply safety measures during the production process.",
    'le_cd'      => '• use locally available materials and tools to make the identified item.',
    'le_sow'     => '• Make the selected item using the sketch made while following instructional steps.',
    'key_inquiry'=> 'How do we apply finishing techniques to the project?',
  ],
  [
    'strand'     => '4.0 Tools and Production', 'sub_strand' => '4.3 Project',
    'slo_cd'     => 'c) make an item to solve the problem identified',
    'slo_sow'    => "Lesson 13:\na) Construct the project item using locally available materials.\nb) Apply safety measures during the production process.",
    'le_cd'      => '• use locally available materials and tools to make the identified item.',
    'le_sow'     => '• Make the selected item using the sketch made while following instructional steps.',
    'key_inquiry'=> 'How do we complete the production of the project item?',
  ],
  [
    'strand'     => '4.0 Tools and Production', 'sub_strand' => '4.3 Project',
    'slo_cd'     => 'c) make an item to solve the problem identified',
    'slo_sow'    => "Lesson 14:\na) Construct the project item using locally available materials.\nb) Apply safety measures during the production process.",
    'le_cd'      => '• use locally available materials and tools to make the identified item.',
    'le_sow'     => '• Make the selected item using the sketch made while following instructional steps.',
    'key_inquiry'=> 'How do we finalise and review the project item?',
  ],
  [
    'strand'     => '4.0 Tools and Production', 'sub_strand' => '4.3 Project',
    'slo_cd'     => 'c) make an item to solve the problem identified',
    'slo_sow'    => "Lesson 15:\na) Present the finished item to the class for feedback.\nb) Evaluate the quality of the item based on peer comments.",
    'le_cd'      => '• present the finished items for feedback.',
    'le_sow'     => '• Present the item made to the whole class and allow comments.',
    'key_inquiry'=> 'How do peers evaluate the finished project item?',
  ],
  [
    'strand'     => '4.0 Tools and Production', 'sub_strand' => '4.3 Project',
    'slo_cd'     => 'd) utilise skills acquired in Pre-Technical Studies to solve problems in day-to-day life',
    'slo_sow'    => "Lesson 16:\na) Apply the produced item to solve the identified local problem.\nb) Report on the effectiveness of the item in a real-life scenario.",
    'le_cd'      => '• utilise skills acquired in Pre-Technical Studies to solve problems in day-to-day life.',
    'le_sow'     => '• Use the item made to solve the problem identified in your locality.',
    'key_inquiry'=> 'How does the project item solve the identified problem?',
  ],

  // ── 5.1 Financial Services ─────────────────────────────────────────────────
  [
    'strand'      => '5.0 Entrepreneurship',
    'sub_strand'  => '5.1 Financial Services',
    'slo_cd'      => 'a) identify financial institutions available in Kenya',
    'slo_sow'     => "Lesson 1:\na) Identify various financial institutions available in Kenya.\nb) Search for information on the roles of different financial institutions.",
    'le_cd'       => '• use print or digital media to search for information on financial institutions available in Kenya.',
    'le_sow'      => "• Identify, in groups, financial institutions available in Kenya.\n• Search for information, in groups, on financial institutions and present findings.",
    'key_inquiry' => 'What financial institutions are available in Kenya?',
  ],
  [
    'strand'      => '5.0 Entrepreneurship',
    'sub_strand'  => '5.1 Financial Services',
    'slo_cd'      => 'b) classify financial institutions in Kenya',
    'slo_sow'     => "Lesson 2:\na) Classify financial institutions into formal and informal categories.\nb) Distinguish between banks, insurance companies, SACCOs, and micro-finance.",
    'le_cd'       => '• discuss and present the types of financial institutions in Kenya (banks, insurance, SACCOs, micro finance).',
    'le_sow'      => "• Classify, in groups, financial institutions in Kenya.\n• Brainstorm the two classifications of financial institutions.",
    'key_inquiry' => 'How are financial institutions classified in Kenya?',
  ],
  [
    'strand'      => '5.0 Entrepreneurship',
    'sub_strand'  => '5.1 Financial Services',
    'slo_cd'      => 'c) analyse services offered by financial institutions in Kenya',
    'slo_sow'     => "Lesson 3:\na) Analyze various services offered by financial institutions through case studies.\nb) Categorize financial services such as savings, credit, and insurance.",
    'le_cd'       => '• use a case study to discuss services offered by financial institutions in Kenya.',
    'le_sow'      => "• Analyse, in groups, a case study on financial institutions.\n• Note down in exercise books the services they offer and share findings.",
    'key_inquiry' => 'What services do financial institutions offer?',
  ],
  [
    'strand'      => '5.0 Entrepreneurship',
    'sub_strand'  => '5.1 Financial Services',
    'slo_cd'      => 'd) utilise financial services for entrepreneurial development',
    'slo_sow'     => "Lesson 4:\na) Identify specific financial services needed to start a small business.\nb) Select appropriate financial institutions for business support in a given scenario.",
    'le_cd'       => '• engage in a discussion with a resource person on the utilisation of financial services for entrepreneurial development.',
    'le_sow'      => "• Picture a scenario of starting a small business.\n• Identify financial services and institutions you would need.",
    'key_inquiry' => 'How can financial services support entrepreneurial growth?',
  ],

  // ── 5.2 Government and Business ───────────────────────────────────────────
  [
    'strand'      => '5.0 Entrepreneurship',
    'sub_strand'  => '5.2 Government and Business',
    'slo_cd'      => 'a) explain the reasons for government involvement in business',
    'slo_sow'     => "Lesson 1:\na) Explain the various reasons why the government gets involved in business.\nb) Identify government roles in business from displayed flashcards.",
    'le_cd'       => '• brainstorm and present on the reasons for government involvement in business in Kenya.',
    'le_sow'      => "• Brainstorming on the reason for government involvement in business.\n• Observe displayed flashcards on reasons for government involvement.",
    'key_inquiry' => 'Why does the government get involved in business?',
  ],
  [
    'strand'      => '5.0 Entrepreneurship',
    'sub_strand'  => '5.2 Government and Business',
    'slo_cd'      => 'b) describe ways of government involvement in business',
    'slo_sow'     => "Lesson 2:\na) Describe different ways the government involves itself in business activities.\nb) Discuss government regulations imposed on businesses.",
    'le_cd'       => '• use print or digital media to search for information on ways of Government involvement in business.',
    'le_sow'      => "• Describe, in groups, ways of government involvement in business.\n• Discuss regulations the government imposes on business.",
    'key_inquiry' => 'How does the government involve itself in business?',
  ],
  [
    'strand'      => '5.0 Entrepreneurship',
    'sub_strand'  => '5.2 Government and Business',
    'slo_cd'      => 'c) explore types of taxes in Kenya',
    'slo_sow'     => "Lesson 3:\na) Define the meaning of taxation in the Kenyan context.\nb) Discuss the importance of citizens and businesses paying taxes.",
    'le_cd'       => '• discuss and present on the meaning and importance of paying taxes in Kenya.',
    'le_sow'      => '• Discuss, in groups, the meaning and importance of paying taxes and share findings.',
    'key_inquiry' => 'What is taxation and why is it important?',
  ],
  [
    'strand'      => '5.0 Entrepreneurship',
    'sub_strand'  => '5.2 Government and Business',
    'slo_cd'      => 'c) explore types of taxes in Kenya',
    'slo_sow'     => "Lesson 4:\na) Identify the different types of taxes in Kenya (VAT, Income Tax, etc.).\nb) Explain how different taxes are applied to businesses.",
    'le_cd'       => '• discuss types of taxes in Kenya (income tax, VAT, corporate tax, fuel levy, excise duty).',
    'le_sow'      => '• Discuss, in groups, types of taxes in Kenya and present to peers.',
    'key_inquiry' => 'What are the different types of taxes in Kenya?',
  ],
  [
    'strand'      => '5.0 Entrepreneurship',
    'sub_strand'  => '5.2 Government and Business',
    'slo_cd'      => 'd) analyse e-Government services in business',
    'slo_sow'     => "Lesson 5:\na) Analyze case studies on e-Government services used in business.\nb) Interact with e-Government platforms using digital tools.",
    'le_cd'       => "• discuss a case scenarios on e-Government services in business.\n• use digital devices to access and interact with e-Government platform in Kenya.",
    'le_sow'      => "• Discuss, in groups, a case study on e-government services in business.\n• Use ICT tools to access and interact with e-Government platforms.",
    'key_inquiry' => 'How are e-Government services used in business?',
  ],
  [
    'strand'      => '5.0 Entrepreneurship',
    'sub_strand'  => '5.2 Government and Business',
    'slo_cd'      => 'e) acknowledge the need to comply with Government regulations in business',
    'slo_sow'     => "Lesson 6:\na) Explain the necessity of complying with government business regulations.\nb) Evaluate understanding of government and business through an exercise.",
    'le_cd'       => '• acknowledge the need to comply with Government regulations in business.',
    'le_sow'      => "• Discuss the need to comply with government regulations in business.\n• Work individually on assessment exercise.",
    'key_inquiry' => 'Why must businesses comply with government regulations?',
  ],

  // ── 5.3 Business Plan ──────────────────────────────────────────────────────
  [
    'strand'      => '5.0 Entrepreneurship',
    'sub_strand'  => '5.3 Business Plan',
    'slo_cd'      => 'a) explain the importance of a business plan in entrepreneurship',
    'slo_sow'     => "Lesson 1:\na) Define the term \"Business Plan.\"\nb) Discuss the importance of a business plan for an entrepreneur.",
    'le_cd'       => '• brainstorm and present on the meaning and importance of a business plan.',
    'le_sow'      => "• Brainstorm on the meaning and importance of a business plan.\n• Present findings in class.",
    'key_inquiry' => 'What is a business plan and why is it important?',
  ],
  [
    'strand'      => '5.0 Entrepreneurship',
    'sub_strand'  => '5.3 Business Plan',
    'slo_cd'      => 'b) describe the components of a business plan in financial management',
    'slo_sow'     => "Lesson 2:\na) Identify the key components of a business plan.\nb) Describe the executive summary and business description components.",
    'le_cd'       => '• brainstorm and present on the components of a business plan.',
    'le_sow'      => '• Identify, in groups, key components of a business plan and present findings.',
    'key_inquiry' => 'What are the key components of a business plan?',
  ],
  [
    'strand'      => '5.0 Entrepreneurship',
    'sub_strand'  => '5.3 Business Plan',
    'slo_cd'      => 'c) fill in a business plan template for a given business project',
    'slo_sow'     => "Lesson 4:\na) Identify the specific sections of a business plan template.\nb) Populate the business description and market analysis sections for a project.",
    'le_cd'       => '• complete a business plan template.',
    'le_sow'      => "• Identify, in pairs, the sections of a business plan template.\n• Fill in the business description and market analysis sections for a chosen project.",
    'key_inquiry' => 'How do we fill in the key sections of a business plan template?',
  ],
  [
    'strand'      => '5.0 Entrepreneurship',
    'sub_strand'  => '5.3 Business Plan',
    'slo_cd'      => 'c) fill in a business plan template',
    'slo_sow'     => "Lesson 5:\na) Complete the marketing plan and financial projection sections of the template.\nb) Present the completed business plan to peers for review.",
    'le_cd'       => '• complete a business plan template.',
    'le_sow'      => "• Complete the remaining sections of the business plan template in pairs.\n• Share the completed business plan with the class for feedback.",
    'key_inquiry' => 'How do we complete and present a business plan?',
  ],
  [
    'strand'      => '5.0 Entrepreneurship',
    'sub_strand'  => '5.3 Business Plan',
    'slo_cd'      => 'd) embrace the use of a business plan in entrepreneurship',
    'slo_sow'     => "Lesson 6:\na) Discuss how a business plan helps in solving common entrepreneurial challenges.\nb) Evaluate personal understanding of business planning through a structured exercise.",
    'le_cd'       => "• engage with a resource person on the use of a business plan in entrepreneurship.\n• discuss how a business plan solves problems.",
    'le_sow'      => "• Discuss, in groups, the role of a business plan in addressing business risks and growth.\n• Work individually on a summative assessment exercise on business plans.",
    'key_inquiry' => 'How does a business plan help an entrepreneur succeed?',
  ],
];

// ── Insert with week/lesson assignment ────────────────────────────────────────
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

foreach ($lessons as $l) {
    $stmt->execute([
        ':laid'        => $learningAreaId,
        ':week'        => $currentWeek,
        ':lesson'      => $lessonInWeek,
        ':strand'      => $l['strand'],
        ':sub_strand'  => $l['sub_strand'],
        ':slo_cd'      => $l['slo_cd'],
        ':slo_sow'     => $l['slo_sow'],
        ':le_cd'       => $l['le_cd'],
        ':le_sow'      => $l['le_sow'],
        ':key_inquiry' => $l['key_inquiry'],
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
<title>Seed Complete</title>
<style>
  body{font-family:Segoe UI,sans-serif;padding:30px;background:#f3f4f6}
  .card{background:#fff;border-radius:8px;padding:28px;max-width:700px;box-shadow:0 1px 4px rgba(0,0,0,.12)}
  h2{color:#1a56db;margin-bottom:8px} table{width:100%;border-collapse:collapse;margin-top:18px}
  th{background:#1a56db;color:#fff;padding:9px 12px;text-align:left}
  td{padding:8px 12px;border-bottom:1px solid #e5e7eb;font-size:13px}
  tr:last-child td{border-bottom:none} a.btn{display:inline-block;margin-top:20px;background:#1a56db;color:#fff;padding:10px 22px;border-radius:6px;text-decoration:none;font-weight:600}
</style></head><body>
<div class="card">
  <h2>&#10003; Seeded Successfully</h2>
  <p>Learning Area: <strong><?= htmlspecialchars($la['name']) ?></strong> (<?= htmlspecialchars($la['grade_name']) ?>)</p>
  <p><strong><?= $inserted ?> lessons</strong> inserted across <strong><?= $totalWeeks ?> weeks</strong> at <?= $LESSONS_PER_WEEK ?> lessons/week.</p>
  <table>
    <tr><th>Strand</th><th>Sub-Strand</th><th>Lessons</th></tr>
    <tr><td>1.0 Foundations of Pre-Technical Studies</td><td>1.1 Safety on Raised Platforms</td><td>6</td></tr>
    <tr><td>1.0 Foundations of Pre-Technical Studies</td><td>1.2 Handling Hazardous Substances</td><td>7</td></tr>
    <tr><td>1.0 Foundations of Pre-Technical Studies</td><td>1.3 Self-Exploration and Career Development</td><td>6</td></tr>
    <tr><td>2.0 Communication in Pre-Technical Studies</td><td>2.1 Oblique Projection</td><td>9</td></tr>
    <tr><td>2.0 Communication in Pre-Technical Studies</td><td>2.2 Visual Programming</td><td>10</td></tr>
    <tr><td>3.0 Materials for Production</td><td>3.1 Wood</td><td>6</td></tr>
    <tr><td>3.0 Materials for Production</td><td>3.2 Handling Waste Materials</td><td>2</td></tr>
    <tr><td>4.0 Tools and Production</td><td>4.1 Holding Tools</td><td>6</td></tr>
    <tr><td>4.0 Tools and Production</td><td>4.2 Driving Tools</td><td>5</td></tr>
    <tr><td>4.0 Tools and Production</td><td>4.3 Project</td><td>16</td></tr>
    <tr><td>5.0 Entrepreneurship</td><td>5.1 Financial Services</td><td>4</td></tr>
    <tr><td>5.0 Entrepreneurship</td><td>5.2 Government and Business</td><td>6</td></tr>
    <tr><td>5.0 Entrepreneurship</td><td>5.3 Business Plan</td><td>5</td></tr>
    <tr><td colspan="2"><strong>Total</strong></td><td><strong><?= $inserted ?></strong></td></tr>
  </table>
  <a class="btn" href="index.php?learning_area_id=<?= $learningAreaId ?>">Open Scheme of Work &rarr;</a>
</div>
</body></html>
