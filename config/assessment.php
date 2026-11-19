<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Indicative English placement assessment blueprints
    |--------------------------------------------------------------------------
    |
    | These are engineering targets for the A1-B2 forms, not validated CEFR
    | certification standards. Each form samples grammar, vocabulary and
    | reading only. Listening, speaking and writing are not measured.
    |
    */
    'placement_blueprints' => [
        'a1' => [
            'slug' => 'english-level-test-a1',
            'label' => 'A1 indicative placement',
            'level_slug' => 'a1',
            'multi_skill' => true,
            'minimum_answer_balance_count' => 12,
            'duration_minutes' => 15,
            'question_count' => 20,
            'question_types' => ['single_choice' => 20],
            'sections' => ['Grammar' => 10, 'Vocabulary' => 6, 'Reading' => 4],
            'difficulty' => ['easy' => 12, 'medium' => 8, 'hard' => 0],
            'minimum_topics' => ['Grammar' => 3, 'Vocabulary' => 1, 'Reading' => 1],
            'required_topics' => ['Present Simple', 'Articles', 'Prepositions', 'Vocabulary', 'Reading'],
            'correct_option_counts' => ['A' => [3, 7], 'B' => [3, 7], 'C' => [3, 7], 'D' => [3, 7]],
        ],
        'a2' => [
            'slug' => 'english-level-test-a2',
            'label' => 'A2 indicative placement',
            'level_slug' => 'a2',
            'multi_skill' => true,
            'minimum_answer_balance_count' => 12,
            'duration_minutes' => 20,
            'question_count' => 20,
            'question_types' => ['single_choice' => 20],
            'sections' => ['Grammar' => 10, 'Vocabulary' => 6, 'Reading' => 4],
            'difficulty' => ['easy' => 8, 'medium' => 8, 'hard' => 4],
            'minimum_topics' => ['Grammar' => 3, 'Vocabulary' => 1, 'Reading' => 1],
            'required_topics' => ['Past Simple', 'Present Continuous', 'Present Perfect', 'Prepositions', 'Vocabulary', 'Reading'],
            'correct_option_counts' => ['A' => [3, 7], 'B' => [3, 7], 'C' => [3, 7], 'D' => [3, 7]],
        ],
        'b1' => [
            'slug' => 'english-level-test-b1',
            'label' => 'B1 indicative placement',
            'level_slug' => 'b1',
            'multi_skill' => true,
            'minimum_answer_balance_count' => 12,
            'duration_minutes' => 25,
            'question_count' => 20,
            'question_types' => ['single_choice' => 20],
            'sections' => ['Grammar' => 10, 'Vocabulary' => 6, 'Reading' => 4],
            'difficulty' => ['easy' => 4, 'medium' => 10, 'hard' => 6],
            'minimum_topics' => ['Grammar' => 4, 'Vocabulary' => 2, 'Reading' => 1],
            'required_topics' => ['Present Perfect', 'Conditionals', 'Modal Verbs', 'Past Continuous', 'Passive Voice', 'Reported Speech', 'Vocabulary', 'Reading'],
            'correct_option_counts' => ['A' => [3, 7], 'B' => [3, 7], 'C' => [3, 7], 'D' => [3, 7]],
        ],
        'b2' => [
            'slug' => 'english-level-test-b2',
            'label' => 'B2 indicative placement',
            'level_slug' => 'b2',
            'multi_skill' => true,
            'minimum_answer_balance_count' => 12,
            'duration_minutes' => 30,
            'question_count' => 20,
            'question_types' => ['single_choice' => 20],
            'sections' => ['Grammar' => 10, 'Vocabulary' => 6, 'Reading' => 4],
            'difficulty' => ['easy' => 2, 'medium' => 8, 'hard' => 10],
            'minimum_topics' => ['Grammar' => 4, 'Vocabulary' => 2, 'Reading' => 1],
            'required_topics' => ['Conditionals', 'Modal Verbs', 'Past Perfect', 'Passive Voice', 'Reported Speech', 'Vocabulary', 'Reading'],
            'correct_option_counts' => ['A' => [3, 7], 'B' => [3, 7], 'C' => [3, 7], 'D' => [3, 7]],
        ],
    ],

    'focused_blueprints' => [
        'grammar-test-present-simple' => [
            'slug' => 'grammar-test-present-simple',
            'label' => 'Present Simple grammar practice',
            'level_slug' => 'a1',
            'multi_skill' => false,
            'minimum_answer_balance_count' => 8,
            'duration_minutes' => 10,
            'question_count' => 8,
            'question_types' => ['single_choice' => 8],
            'sections' => ['Grammar' => 8],
            'difficulty' => ['easy' => 4, 'medium' => 4, 'hard' => 0],
            'minimum_topics' => ['Grammar' => 1],
            'required_topics' => ['Present Simple'],
            'correct_option_counts' => ['A' => [1, 3], 'B' => [1, 3], 'C' => [1, 3], 'D' => [1, 3]],
        ],
        'grammar-test-past-simple' => [
            'slug' => 'grammar-test-past-simple',
            'label' => 'Past Simple grammar practice',
            'level_slug' => 'a2',
            'multi_skill' => false,
            'minimum_answer_balance_count' => 8,
            'duration_minutes' => 10,
            'question_count' => 8,
            'question_types' => ['single_choice' => 8],
            'sections' => ['Grammar' => 8],
            'difficulty' => ['easy' => 3, 'medium' => 4, 'hard' => 1],
            'minimum_topics' => ['Grammar' => 1],
            'required_topics' => ['Past Simple'],
            'correct_option_counts' => ['A' => [1, 3], 'B' => [1, 3], 'C' => [1, 3], 'D' => [1, 3]],
        ],
        'vocabulary-test-everyday-english' => [
            'slug' => 'vocabulary-test-everyday-english',
            'label' => 'Everyday English vocabulary practice',
            'level_slug' => 'a2',
            'multi_skill' => false,
            'minimum_answer_balance_count' => 8,
            'duration_minutes' => 12,
            'question_count' => 8,
            'question_types' => ['single_choice' => 8],
            'sections' => ['Vocabulary' => 8],
            'difficulty' => ['easy' => 4, 'medium' => 4, 'hard' => 0],
            'minimum_topics' => ['Vocabulary' => 1],
            'required_topics' => ['Vocabulary'],
            'correct_option_counts' => ['A' => [1, 3], 'B' => [1, 3], 'C' => [1, 3], 'D' => [1, 3]],
        ],
    ],
];
