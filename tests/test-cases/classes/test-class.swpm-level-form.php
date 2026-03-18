<?php

class SwpmLevelFormTest extends WP_UnitTestCase_Custom {
    /**
     * @dataProvider level_form_data
     *
     * @return void
     */
    public function test_is_valid($post_data, $expected): void {
        $_POST = $post_data;
        $level = SwpmTransfer::$default_level_fields;
        $form = new SwpmLevelForm($level);

        $this->assertEquals($expected, $form->is_valid());
    }

    /**
     * @return array
     */
    protected function level_form_data(): array {
        return [
            'valid-data' => [
                [
                    'alias' => 'test-level',
                    'role' => 'author',
                    'subscription_period' => '',
                    'subscription_duration_type' => SwpmMembershipLevel::NO_EXPIRY
                ],
                true
            ],
            'valid-data-annual-fixed-date' => [
                [
                    'alias' => 'test-level',
                    'role' => 'author',
                    'subscription_period_'.SwpmMembershipLevel::ANNUAL_FIXED_DATE => [
                        'd' => 20,
                        'm' => 12,
                    ],
                    'subscription_duration_type' => SwpmMembershipLevel::ANNUAL_FIXED_DATE
                ],
                true
            ],
            
            'invalid-data-annual-fixed-date' => [
                [
                    'alias' => 'test-level',
                    'role' => 'author',
                    'subscription_period_'.SwpmMembershipLevel::ANNUAL_FIXED_DATE => 'not-array',
                    'subscription_duration_type' => SwpmMembershipLevel::ANNUAL_FIXED_DATE
                ],
                false
            ]
        ];
    }
}
