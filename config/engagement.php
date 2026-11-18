<?php

return [
    'xp' => [
        'lesson_completed' => 20,
        'adaptive_practice_completed' => 10,
        'vocabulary_review_completed' => 2,
    ],

    'daily_limits' => [
        'adaptive_practice' => 3,
        'vocabulary_review' => 10,
    ],

    'daily_goals' => [10, 20, 30, 50],

    'achievements' => [
        'first_lesson' => ['title' => 'First steps', 'description' => 'Complete your first lesson.'],
        'lessons_10' => ['title' => 'Building momentum', 'description' => 'Complete 10 lessons.'],
        'first_practice' => ['title' => 'Practice makes progress', 'description' => 'Complete your first adaptive practice session.'],
        'vocabulary_50' => ['title' => 'Word collector', 'description' => 'Complete 50 vocabulary-review awards.'],
        'streak_3' => ['title' => 'Three-day rhythm', 'description' => 'Reach your daily goal three days in a row.'],
        'streak_7' => ['title' => 'A week of learning', 'description' => 'Reach your daily goal seven days in a row.'],
        'first_skill_mastery' => ['title' => 'Skill builder', 'description' => 'Build enough evidence to establish mastery in a skill.'],
        'three_skills_mastered' => ['title' => 'Well-rounded learner', 'description' => 'Demonstrate at least 80% mastery in three skills.'],
        'xp_100' => ['title' => 'Century', 'description' => 'Earn 100 XP.'],
        'xp_500' => ['title' => 'Dedicated learner', 'description' => 'Earn 500 XP.'],
    ],
];
