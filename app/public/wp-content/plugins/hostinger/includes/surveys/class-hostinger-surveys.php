<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Hostinger_Surveys {
	private const SUBMIT_SURVEY = '/v3/wordpress/survey/store';
	private const GET_SURVEY = '/v3/wordpress/survey/get';
	private const CLIENT_SURVEY_ELIGIBILITY = '/v3/wordpress/survey/client-eligible';
	private const CLIENT_SURVEY_IDENTIFIER = 'customer_satisfaction_score';
	private const CLIENT_WOO_COMPLETED_ACTIONS = 'woocommerce_task_list_tracked_completed_tasks';
	private const WOO_SURVEY_IDENTIFIER = 'wordpress_woocommerce_onboarding';
	private const LOCATION_SLUG = 'location';
	private const WOOCOMMERCE_PAGES = array(
		'/wp-admin/admin.php?page=wc-admin',
		'/wp-admin/edit.php?post_type=shop_order',
		'/wp-admin/admin.php?page=wc-admin&path=/customers',
		'/wp-admin/edit.php?post_type=shop_coupon&legacy_coupon_menu=1',
		'/wp-admin/admin.php?page=wc-admin&path=/marketing',
		'/wp-admin/admin.php?page=wc-reports',
		'/wp-admin/admin.php?page=wc-settings',
		'/wp-admin/admin.php?page=wc-status',
		'/wp-admin/admin.php?page=wc-admin&path=/extensions',
		'/wp-admin/edit.php?post_type=product',
		'/wp-admin/post-new.php?post_type=product',
		'/wp-admin/edit.php?post_type=product&page=product-reviews',
		'/wp-admin/edit.php?post_type=product&page=product_attributes',
		'/wp-admin/edit-tags.php?taxonomy=product_cat&post_type=product',
		'/wp-admin/edit-tags.php?taxonomy=product_tag&post_type=product',
		'/wp-admin/admin.php?page=wc-admin&path=/analytics/overview',
		'/wp-admin/admin.php?page=wc-admin'
	);

	private array $required_survey_items = array(
		array(
			'question_slug' => self::LOCATION_SLUG,
			'answer'        => 'wordpress_cms'
		)
	);
	private Hostinger_Config $config_handler;
	private Hostinger_Settings $settings;
	private Hostinger_Requests_Client $client;
	private Hostinger_Helper $helper;
	private Hostinger_Surveys_Questions $survey_questions;

	public function __construct() {
		$this->settings         = new Hostinger_Settings();
		$this->helper           = new Hostinger_Helper();
		$this->config_handler   = new Hostinger_Config();
		$this->survey_questions = new Hostinger_Surveys_Questions();
		$this->client           = new Hostinger_Requests_Client( $this->config_handler->get_config_value( 'base_rest_uri', HOSTINGER_REST_URI ), [
			Hostinger_Config::TOKEN_HEADER  => $this->helper::get_api_token(),
			Hostinger_Config::DOMAIN_HEADER => $this->helper->get_host_info()
		] );

		if ( $this->is_woocommerce_survey_enabled() ) {
			add_action( 'admin_footer', [ $this, 'woocommerce_csat_survey' ], 10, 2 );
		}

	}

	public function is_woocommerce_admin_page(): bool {
		$current_uri = sanitize_text_field( $_SERVER['REQUEST_URI'] );

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return false;
		}

		if ( isset( $current_uri ) && strpos( $current_uri, '/wp-json/' ) !== false ) {
			return false;
		}

		if ( in_array( $current_uri, self::WOOCOMMERCE_PAGES ) ) {
			return true;
		}

		return false;

	}

	public function is_survey_enabled(): bool {
		return ! $this->settings->get_setting( 'feedback_survey_completed' ) && $this->settings->get_setting( 'content_published' ) && $this->is_client_eligible();
	}

	public function is_woocommerce_survey_enabled(): bool {
		return ! $this->settings->get_setting( 'woocommerce_survey_completed' ) && $this->settings->get_setting( 'survey.website.type' ) && $this->is_woocommerce_admin_page() && $this->default_woocommerce_survey_completed() && $this->is_client_eligible();
	}

	public function default_woocommerce_survey_completed(): bool {
		$completed_actions          = get_option( self::CLIENT_WOO_COMPLETED_ACTIONS, array() );
		$required_completed_actions = array( 'products', 'appearance', 'payments' );

		return empty( array_diff( $required_completed_actions, $completed_actions ) );
	}

	public function is_client_eligible(): bool {
		$response = $this->client->get( self::CLIENT_SURVEY_ELIGIBILITY, array(
			'identifier' => self::CLIENT_SURVEY_IDENTIFIER,
		) );

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( is_wp_error( $response ) || $response_code !== 200 ) {
			return false;
		}

		$response_data = json_decode( $response_body );

		if ( isset( $response_data->data ) && $response_data->data === true ) {
			return true;
		}

		return false;
	}

	private function get_survey_questions(): array {
		$response = $this->client->get( self::GET_SURVEY, array(
			'identifier' => self::CLIENT_SURVEY_IDENTIFIER,
		) );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( $response_code !== 200 || empty( $response_body ) ) {
			return array();
		}

		$response_data = json_decode( $response_body, true );

		if ( isset( $response_data['data']['questions'] ) && is_array( $response_data['data']['questions'] ) ) {
			return $response_data['data']['questions'];
		}

		return array();
	}

	public function submit_survey_answers( array $answers, string $survey_type ): void {
		$required_survey_items = $this->get_required_survey_items( $survey_type );
		$data = array(
			'identifier' => self::CLIENT_SURVEY_IDENTIFIER,
			'answers'    => $required_survey_items,
		);

		$this->add_user_answers( $data, $answers );
		$response = $this->submit_survey_data( $data );

		if ( is_wp_error( $response ) ) {
			$this->handle_failed_survey( $response );
		} else {
			$this->handle_successful_survey( $response, $survey_type );
		}
	}

	private function get_required_survey_items( string $survey_type ): array {
		if ( $survey_type === 'woo_survey' ) {
			return array(
				array(
					'question_slug' => self::LOCATION_SLUG,
					'answer'        => self::WOO_SURVEY_IDENTIFIER,
				),
			);
		} else {
			return $this->required_survey_items;
		}
	}

	private function add_user_answers( array &$data, array $answers ): void {
		foreach ( $answers as $answer_slug => $answer ) {
			$data['answers'][] = [
				'question_slug' => $answer_slug,
				'answer'        => $answer,
			];
		}
	}

	private function submit_survey_data( array $data ) {
		return $this->client->post( self::SUBMIT_SURVEY, $data );
	}

	private function handle_failed_survey( $response ): void {
		error_log( print_r( $response, true ) );
		wp_send_json_error( __( 'Survey failed', 'hostinger' ) );
	}

	private function handle_successful_survey( $response, string $survey_type ): void {
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $response_code === 200 && $response_data['success'] ) {
			$setting_key = ( $survey_type === 'woo_survey' ) ? 'woocommerce_survey_completed' : 'feedback_survey_completed';
			$this->settings->update_setting( $setting_key, true );
			wp_send_json( __( 'Survey completed', 'hostinger' ) );
		}
	}

	public function get_wp_survey_questions(): array {
		$all_questions  = $this->get_survey_questions();
		$question_slugs = array( 'score', 'comment' );

		return $this->filter_questions_by_slug( $all_questions, $question_slugs );
	}

	private function filter_questions_by_slug( array $all_questions, $question_slugs ): array {
		$questions_with_required_rule = [];

		foreach ( $all_questions as $question ) {
			if ( isset( $question['slug'] ) && in_array( $question['slug'], $question_slugs ) ) {
				$questions_with_required_rule[] = [
					'slug'  => $question['slug'],
					'rules' => $question['rules']
				];
			}
		}

		return $questions_with_required_rule;
	}

	private function is_survey_question_required( array $question ): bool {
		return isset( $question['rules'] ) && in_array( 'required', $question['rules'] );
	}

	public function generate_json( array $survey_questions, string $survey_type = 'ai_survey' ): string {
		$jsonStructure = [
			"pages"               => [],
			"showQuestionNumbers" => "off",
			"showTOC"             => false,
			"pageNextText"        => __( 'Next', 'hostinger' ),
			"pagePrevText"        => __( 'Previous', 'hostinger' ),
			"completeText"        => __( 'Submit', 'hostinger' ),
			"completedHtml"       => __( 'Thank you for completing the survey !', 'hostinger' ),
			"requiredText"        => '*',
		];

		foreach ( $survey_questions as $question ) {

			if ( $survey_type === 'woo_survey' ) {
				$title = $this->survey_questions->map_survey_questions( $question['slug'] )['woo_question'];
			} else {
				$title = $this->survey_questions->map_survey_questions( $question['slug'] )['question'];
			}

			$element = [
				"type"              => $this->survey_questions->map_survey_questions( $question['slug'] )['type'],
				"name"              => $question['slug'],
				"title"             => $title,
				"requiredErrorText" => __( 'Response required.', 'hostinger' ),
			];

			if ( $question['slug'] == 'comment' ) {
				$element['maxLength'] = 250;
			}

			if ( isset( $question['rules'] ) ) {

				$betweenRule = $this->get_between_rule_values( $question['rules'] );
				if ( $betweenRule ) {
					$element["rateMin"]            = $betweenRule[0];
					$element["rateMax"]            = $betweenRule[1];
					$element["minRateDescription"] = __( 'Poor', 'hostinger' );
					$element["maxRateDescription"] = __( 'Excellent', 'hostinger' );
				}

				if ( $this->is_survey_question_required( $question ) ) {
					$element["isRequired"] = true;
				}

			}

			$question_data = [
				"name"     => $question['slug'],
				"elements" => [ $element ]
			];

			$jsonStructure["pages"][] = $question_data;
		}

		return json_encode( $jsonStructure );
	}

	public function get_between_rule_values( array $rules ): array {
		foreach ( $rules as $rule ) {
			if ( strpos( $rule, 'between:' ) === 0 ) {
				$betweenValues = explode( ',', substr( $rule, 8 ) );
				if ( count( $betweenValues ) === 2 ) {
					return $betweenValues;
				}
			}
		}

		return [];
	}

	public static function woocommerce_csat_survey(): void {
		ob_start();
		?>
		<div class="hts-survey-wrapper hts-woocommerce-csat">
			<div id="hostinger-feedback-survey"></div>
			<div id="hts-questionsLeft">
				<span id="hts-currentQuestion">1</span> <?php echo esc_html__( __( 'Question', 'hostinger' ) ); ?> <?php echo esc_html__( __( 'of ', 'hostinger' ) ); ?>
				<span id="hts-allQuestions"></span></div>
		</div>
		<?php
		echo ob_get_clean();
	}

}

$surveys = new Hostinger_Surveys();