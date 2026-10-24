# Test Coverage Audit

Tanggal audit: **22 Oktober 2026 (Asia/Jakarta)**. Project: `D:\Compro`. Audit read-only terhadap kode aplikasi dan database utama; artefak audit disimpan di `docs/audit`.

## Eksekusi aktual

| Pemeriksaan | Hasil | Bukti |
| --- | --- | --- |
| php artisan test, isolated copy | 237 PASS;15.133 assertions;0 errors/failures;199.66detik | evidence/tests-isolated.txt; phpunit-isolated.xml |
| php artisan test, empty :memory: | 33 PASS;204 FAIL;174 assertions;81.02detik | evidence/tests.txt; phpunit.xml — schema users tidak ada |
| npm run build | PASS; Vite6.4.4,63modules;32.25detik | evidence/build.txt |
| route:list | 55 routes | evidence/routes.json |
| migrate:status | 11 Ran pada SQLite lokal | evidence/migrations.txt |
| schedule:list | Hanya inspire; sync MISSING | evidence/schedule.txt |
| composer check-platform-reqs | PASS; semua ekstensi required tersedia | evidence/platform.txt |
| composer audit --locked | 4 records/3unique GHSA; exit1 | evidence/composer-audit.json |
| npm audit | 0 advisories; exit0 | evidence/npm-audit.json |
| Independent functional probes | Acceptance FAIL di luar existing suite | evidence/probes.php; probes.json |
| Browser four viewports | 8 layout FAIL; HTTP/access diperiksa terpisah | evidence/browser.json; screenshots |
| CSV/PDF contents | CSV parsed; PDF23pages,3 sample renders | evidence/sample-report.*; pdf-check.json; csv-check.json |


## Isolasi dan baseline

Perintah default phpunit.xml menunjuk database/database.sqlite yang berisi data project; beberapa suite menjalankan delete() tanpa RefreshDatabase. Audit tidak menjalankannya pada database utama. Test existing dilakukan dengan DB_DATABASE menunjuk salinan khusus; probe baru pada SQLite :memory: dan migration hanya pada memori. Tidak memakai migrate:fresh/db:wipe, tidak menjalankan sync/hardware pada database utama.

Baseline235/12.391 (Phase 16) dan237/14.263 (Phase 16.1) berbeda dengan aktual237/15.133. Assertion dapat berubah karena test bergantung data existing dan count fixture; jumlah yang naik tidak otomatis berarti coverage lebih baik. Run memori menunjukkan kekurangan test setup, bukan237bug aplikasi.

## Coverage

| Dimensi | Metode | Numerator / denominator | Hasil |
| --- | --- | --- | --- |
| Implementation completeness (indikatif) | COMPLETE/BUG=1 (fitur ada), PARTIAL=0.5, lainnya0; tidak setara berhasil | 317.5 / 407 | 78.0% |
| Functional verification coverage | Bobot kontrol dengan perilaku diuji (PASS ataupun FAIL); static-only tidak dihitung | 303 / 407 | 74.4% |
| Verified functional completeness | Hanya kontrol audit COMPLETE; PARTIAL/BUG/NV/eksternal0 | 210 / 407 | 51.6% |
| Requirement compliance SRS | Requirement COMPLETE saja, termasuk requirement hilang/opsional dalam denominator | 81 / 283 | 28.6% |
| External integration readiness | 5 gerbang bobot5: mock pipeline PASS; sandbox/live/physical/agronomy belum PASS | 5 / 25 | 20.0% |

Tidak tersedia Xdebug/PCOV untuk line/branch coverage; **LOC coverage NOT MEASURED**. Test bernama concurrent_updates bukan20 pengguna paralel atau MySQL locking test. Mock timeout/rate-limit tidak memverifikasi adapter Google live. Response PDF200/header saja bukan bukti layout: audit membaca teks file dan merender sampel.

## Kebutuhan tanpa test memadai

Skor eksternal0, toggle semua ingress, guest read API, API replay idempotence, Sheets insert/edit/reorder, unknown/revoked device Sheets, source/condition pada ringkasan, nullable pH blueprint, canonical provenance, real MySQL concurrency, account/fruit modules, stale alarm, UAT, accessibility, TLS production, backup restore dan 20 concurrent users. Beberapa kegagalan sekarang mempunyai probe audit tetapi belum regresi PHPUnit resmi.

## Daftar testcase aktual

Daftar ini mengikat testcase yang benar-benar dijalankan ke file. Requirement→implementation→testcase→status terdapat di RTM dan feature matrix; testcase positif bukan bukti gap tertutup.

| File / Class | Test Case | Assertions | Actual Result |
| --- | --- | --- | --- |
| tests/Unit/ExampleTest.php | test_that_true_is_true | 1 | PASS (isolated dataset) |
| tests/Unit/FuzzyMathematicalValidationTest.php | test_triangular_membership_function_mathematics | 8 | PASS (isolated dataset) |
| tests/Unit/FuzzyMathematicalValidationTest.php | test_triangular_membership_function_rejects_inverted_params | 1 | PASS (isolated dataset) |
| tests/Unit/FuzzyMathematicalValidationTest.php | test_trapezoidal_membership_function_mathematics | 22 | PASS (isolated dataset) |
| tests/Unit/FuzzyMathematicalValidationTest.php | test_trapezoidal_membership_function_rejects_invalid_params | 1 | PASS (isolated dataset) |
| tests/Unit/FuzzyMathematicalValidationTest.php | test_trapezoidal_membership_function_rejects_dual_shoulder | 1 | PASS (isolated dataset) |
| tests/Unit/FuzzyMathematicalValidationTest.php | test_scenario_a_optimal_parameters | 6 | PASS (isolated dataset) |
| tests/Unit/FuzzyMathematicalValidationTest.php | test_scenario_b_heat_stress_dry_soil | 6 | PASS (isolated dataset) |
| tests/Unit/FuzzyMathematicalValidationTest.php | test_scenario_c_high_humidity_saturated_soil | 6 | PASS (isolated dataset) |
| tests/Unit/FuzzyMathematicalValidationTest.php | test_scenario_d_acidic_ph | 6 | PASS (isolated dataset) |
| tests/Unit/FuzzyMathematicalValidationTest.php | test_scenario_e_alkaline_ph | 6 | PASS (isolated dataset) |
| tests/Unit/FuzzyMathematicalValidationTest.php | test_scenario_f_multi_rule_overlap | 6 | PASS (isolated dataset) |
| tests/Unit/FuzzyMathematicalValidationTest.php | test_scenario_g_exact_boundary_points | 5 | PASS (isolated dataset) |
| tests/Unit/FuzzyMathematicalValidationTest.php | test_scenario_h_two_sets_overlap | 5 | PASS (isolated dataset) |
| tests/Unit/FuzzyMathematicalValidationTest.php | test_scenario_i_insufficient_rule_coverage | 5 | PASS (isolated dataset) |
| tests/Unit/FuzzyMathematicalValidationTest.php | test_scenario_j_missing_sensor | 2 | PASS (isolated dataset) |
| tests/Unit/FuzzyMathematicalValidationTest.php | test_scenario_k_extreme_out_of_domain | 3 | PASS (isolated dataset) |
| tests/Unit/FuzzyMathematicalValidationTest.php | test_scenario_l_single_rule_active | 7 | PASS (isolated dataset) |
| tests/Unit/FuzzyMathematicalValidationTest.php | test_scenario_m_multiple_rules_same_consequent | 4 | PASS (isolated dataset) |
| tests/Unit/FuzzyMathematicalValidationTest.php | test_scenario_n_multiple_rules_different_consequents | 4 | PASS (isolated dataset) |
| tests/Unit/FuzzyMathematicalValidationTest.php | test_scenario_o_pure_shoulders | 4 | PASS (isolated dataset) |
| tests/Unit/FuzzyMathematicalValidationTest.php | test_scenario_p_sampling_resolution_sensitivity | 9 | PASS (isolated dataset) |
| tests/Feature/ActuatorSimulationTest.php | test_admin_can_turn_on_pump_simulation | 6 | PASS (isolated dataset) |
| tests/Feature/ActuatorSimulationTest.php | test_admin_can_turn_off_pump_simulation | 3 | PASS (isolated dataset) |
| tests/Feature/ActuatorSimulationTest.php | test_admin_can_control_fan_simulation | 6 | PASS (isolated dataset) |
| tests/Feature/ActuatorSimulationTest.php | test_admin_can_toggle_actuator | 3 | PASS (isolated dataset) |
| tests/Feature/ActuatorSimulationTest.php | test_pengamat_cannot_control_actuator_forbidden_403 | 2 | PASS (isolated dataset) |
| tests/Feature/ActuatorSimulationTest.php | test_guest_is_redirected_to_login | 6 | PASS (isolated dataset) |
| tests/Feature/ActuatorSimulationTest.php | test_command_history_is_recorded | 1 | PASS (isolated dataset) |
| tests/Feature/ActuatorSimulationTest.php | test_invalid_command_is_rejected | 3 | PASS (isolated dataset) |
| tests/Feature/ActuatorSimulationTest.php | test_duplicate_repeated_command_handled_idempotently | 4 | PASS (isolated dataset) |
| tests/Feature/ActuatorSimulationTest.php | test_concurrent_updates_safe_with_pessimistic_lock | 3 | PASS (isolated dataset) |
| tests/Feature/ActuatorSimulationTest.php | test_strictly_no_hardware_communication | 3 | PASS (isolated dataset) |
| tests/Feature/ActuatorSimulationTest.php | test_dashboard_displays_actuator_status_summary | 6 | PASS (isolated dataset) |
| tests/Feature/ActuatorSimulationTest.php | test_actuator_index_page_rendered | 5 | PASS (isolated dataset) |
| tests/Feature/ActuatorSimulationTest.php | test_history_filtering_works | 6 | PASS (isolated dataset) |
| tests/Feature/ActuatorSimulationTest.php | test_disabled_actuator_rejects_command | 2 | PASS (isolated dataset) |
| tests/Feature/ActuatorSimulationTest.php | test_pengamat_can_view_index_and_history_read_only | 4 | PASS (isolated dataset) |
| tests/Feature/ActuatorSimulationTest.php | test_json_ajax_control_response | 2 | PASS (isolated dataset) |
| tests/Feature/AlarmTest.php | test_sensor_normal_does_not_produce_alarm | 1 | PASS (isolated dataset) |
| tests/Feature/AlarmTest.php | test_sensor_above_maximum_produces_alarm | 1 | PASS (isolated dataset) |
| tests/Feature/AlarmTest.php | test_sensor_below_minimum_produces_alarm | 1 | PASS (isolated dataset) |
| tests/Feature/AlarmTest.php | test_repeated_abnormal_readings_do_not_duplicate_active_alarm | 4 | PASS (isolated dataset) |
| tests/Feature/AlarmTest.php | test_sensor_returning_to_normal_resolves_alarm | 4 | PASS (isolated dataset) |
| tests/Feature/AlarmTest.php | test_sensor_abnormal_again_after_resolved_creates_new_alarm | 3 | PASS (isolated dataset) |
| tests/Feature/AlarmTest.php | test_null_sensor_values_do_not_produce_false_alarms | 1 | PASS (isolated dataset) |
| tests/Feature/AlarmTest.php | test_legacy_payload_remains_compatible | 1 | PASS (isolated dataset) |
| tests/Feature/AlarmTest.php | test_partial_sensor_payload_remains_compatible | 2 | PASS (isolated dataset) |
| tests/Feature/AlarmTest.php | test_alarm_generated_from_api_ingestion | 2 | PASS (isolated dataset) |
| tests/Feature/AlarmTest.php | test_alarm_generated_from_web_form_submission | 3 | PASS (isolated dataset) |
| tests/Feature/AlarmTest.php | test_rbac_admin_and_pengamat_access_control | 7 | PASS (isolated dataset) |
| tests/Feature/AlarmTest.php | test_threshold_validation_rules | 6 | PASS (isolated dataset) |
| tests/Feature/AlarmTest.php | test_critical_severity_alarm_detection | 4 | PASS (isolated dataset) |
| tests/Feature/AlarmTest.php | test_out_of_order_monitoring_data_handling | 2 | PASS (isolated dataset) |
| tests/Feature/AlarmTest.php | test_dashboard_and_alarm_history_rendering_and_filtering | 11 | PASS (isolated dataset) |
| tests/Feature/AlarmTest.php | test_compatibility_phase_1_to_11_maintained | 4 | PASS (isolated dataset) |
| tests/Feature/AlarmTest.php | test_only_four_allowed_parameters_generate_alarms | 8 | PASS (isolated dataset) |
| tests/Feature/AlarmTest.php | test_non_alarm_parameters_do_not_generate_alarms_but_data_is_stored | 7 | PASS (isolated dataset) |
| tests/Feature/AlarmTest.php | test_legacy_alarms_reconcile_policy_resolves_without_deletion | 5 | PASS (isolated dataset) |
| tests/Feature/Api/DashboardApiTest.php | test_can_get_dashboard_summary | 16 | PASS (isolated dataset) |
| tests/Feature/Api/DashboardApiTest.php | test_dashboard_summary_matches_database_counts | 3 | PASS (isolated dataset) |
| tests/Feature/Api/DashboardApiTest.php | test_api_endpoints_do_not_expose_sensitive_data | 6 | PASS (isolated dataset) |
| tests/Feature/Api/MonitoringApiTest.php | test_can_get_all_monitorings | 11766 | PASS (isolated dataset) |
| tests/Feature/Api/MonitoringApiTest.php | test_can_filter_monitorings_by_plant_id | 731 | PASS (isolated dataset) |
| tests/Feature/Api/MonitoringApiTest.php | test_can_filter_monitorings_by_condition | 2 | PASS (isolated dataset) |
| tests/Feature/Api/MonitoringApiTest.php | test_can_filter_monitorings_by_date | 2 | PASS (isolated dataset) |
| tests/Feature/Api/MonitoringApiTest.php | test_can_get_monitoring_detail | 21 | PASS (isolated dataset) |
| tests/Feature/Api/MonitoringApiTest.php | test_nonexistent_monitoring_returns_404_json | 2 | PASS (isolated dataset) |
| tests/Feature/Api/MonitoringApiTest.php | test_monitoring_statistics_endpoint_works | 42 | PASS (isolated dataset) |
| tests/Feature/Api/MonitoringApiTest.php | test_monitoring_statistics_supports_filtering | 2 | PASS (isolated dataset) |
| tests/Feature/Api/MonitoringApiTest.php | test_invalid_query_parameters_return_422_validation_error | 12 | PASS (isolated dataset) |
| tests/Feature/Api/MonitoringIngestionTest.php | test_successful_sensor_monitoring_ingestion | 3 | PASS (isolated dataset) |
| tests/Feature/Api/MonitoringIngestionTest.php | test_ingestion_with_invalid_plant_id_returns_422 | 4 | PASS (isolated dataset) |
| tests/Feature/Api/MonitoringIngestionTest.php | test_ingestion_missing_required_fields_returns_422 | 11 | PASS (isolated dataset) |
| tests/Feature/Api/MonitoringIngestionTest.php | test_ingestion_with_invalid_numeric_values_returns_422 | 8 | PASS (isolated dataset) |
| tests/Feature/Api/MonitoringIngestionTest.php | test_ingestion_with_invalid_condition_returns_422 | 4 | PASS (isolated dataset) |
| tests/Feature/Api/MonitoringIngestionTest.php | test_existing_monitoring_api_retrieves_ingested_record | 5 | PASS (isolated dataset) |
| tests/Feature/Api/MonitoringIngestionTest.php | test_dashboard_compatibility_after_sensor_ingestion | 4 | PASS (isolated dataset) |
| tests/Feature/Api/PlantApiTest.php | test_can_get_all_plants | 1056 | PASS (isolated dataset) |
| tests/Feature/Api/PlantApiTest.php | test_can_get_plant_detail | 11 | PASS (isolated dataset) |
| tests/Feature/Api/PlantApiTest.php | test_nonexistent_plant_returns_404_json | 2 | PASS (isolated dataset) |
| tests/Feature/Api/PlantApiTest.php | test_can_filter_plants_by_status | 77 | PASS (isolated dataset) |
| tests/Feature/Api/PlantApiTest.php | test_invalid_plant_status_filter_returns_422 | 4 | PASS (isolated dataset) |
| tests/Feature/AuthenticationTest.php | test_login_screen_can_be_rendered | 3 | PASS (isolated dataset) |
| tests/Feature/AuthenticationTest.php | test_admin_can_authenticate_and_redirected_to_dashboard | 3 | PASS (isolated dataset) |
| tests/Feature/AuthenticationTest.php | test_regular_user_can_authenticate_and_redirected_to_dashboard | 3 | PASS (isolated dataset) |
| tests/Feature/AuthenticationTest.php | test_users_cannot_authenticate_with_invalid_password | 3 | PASS (isolated dataset) |
| tests/Feature/AuthenticationTest.php | test_users_cannot_authenticate_with_non_existent_email | 3 | PASS (isolated dataset) |
| tests/Feature/AuthenticationTest.php | test_authenticated_user_can_logout | 3 | PASS (isolated dataset) |
| tests/Feature/AuthenticationTest.php | test_guest_is_redirected_to_login_when_accessing_dashboard | 2 | PASS (isolated dataset) |
| tests/Feature/AuthenticationTest.php | test_admin_user_can_access_admin_route | 2 | PASS (isolated dataset) |
| tests/Feature/AuthenticationTest.php | test_regular_user_cannot_access_admin_route | 1 | PASS (isolated dataset) |
| tests/Feature/DashboardTest.php | test_guest_is_redirected_to_login_when_accessing_dashboard | 2 | PASS (isolated dataset) |
| tests/Feature/DashboardTest.php | test_authenticated_user_can_access_dashboard | 12 | PASS (isolated dataset) |
| tests/Feature/DashboardTest.php | test_regular_user_cannot_see_admin_section_or_menu | 3 | PASS (isolated dataset) |
| tests/Feature/DashboardTest.php | test_admin_can_access_dashboard_and_see_admin_section | 6 | PASS (isolated dataset) |
| tests/Feature/DashboardTest.php | test_dashboard_displays_accurate_counts_from_database | 3 | PASS (isolated dataset) |
| tests/Feature/DashboardTest.php | test_dashboard_displays_recent_monitoring_data | 3 | PASS (isolated dataset) |
| tests/Feature/DashboardTest.php | test_dashboard_plant_filter_works_properly | 2 | PASS (isolated dataset) |
| tests/Feature/DashboardTest.php | test_structured_navigation_routes_redirect_to_dashboard | 6 | PASS (isolated dataset) |
| tests/Feature/DashboardTest.php | test_admin_navigation_routes_protected_by_role_middleware | 6 | PASS (isolated dataset) |
| tests/Feature/DashboardTest.php | test_dashboard_renders_growth_trends_and_distribution_charts | 7 | PASS (isolated dataset) |
| tests/Feature/DashboardTest.php | test_dashboard_date_filter_works_properly | 2 | PASS (isolated dataset) |
| tests/Feature/DashboardTest.php | test_dashboard_quick_range_filter_works | 4 | PASS (isolated dataset) |
| tests/Feature/DashboardTest.php | test_dashboard_handles_empty_filtered_data_gracefully | 2 | PASS (isolated dataset) |
| tests/Feature/DashboardTest.php | test_dashboard_displays_parameter_aggregations | 10 | PASS (isolated dataset) |
| tests/Feature/DashboardTest.php | test_dashboard_rejects_invalid_filter_parameters | 4 | PASS (isolated dataset) |
| tests/Feature/DashboardTest.php | test_monitoring_index_has_link_to_dashboard_analytics | 3 | PASS (isolated dataset) |
| tests/Feature/ExampleTest.php | test_the_application_returns_a_successful_response | 1 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_01_triangular_membership_function | 7 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_02_trapezoidal_membership_function | 13 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_03_membership_boundary_values | 6 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_04_and_minimum_operator | 2 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_05_or_maximum_operator | 1 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_06_rule_activation_and_firing_strength | 3 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_07_implication_mamdani_minimum | 406 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_08_aggregation_maximum | 2 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_09_centroid_defuzzification | 5 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_10_deterministic_score_reproducibility | 2 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_11_valid_input_end_to_end_evaluation | 6 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_12_missing_sensor_produces_insufficient_data | 5 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_13_invalid_sensor_value_produces_insufficient_data | 2 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_14_uncovered_rule_produces_insufficient_rule_coverage | 2 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_15_duplicate_inference_prevention | 3 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_16_rule_versioning_support | 3 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_17_external_fuzzy_result_preservation | 3 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_18_web_monitoring_integration | 5 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_19_rest_api_integration | 3 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_20_google_sheets_integration_mock | 4 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_21_python_simulator_compatibility | 2 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_22_dashboard_rendering_and_provisional_label | 4 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_23_history_page_and_filters | 3 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_24_admin_rbac_fuzzy_config | 5 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_25_pengamat_rbac_fuzzy_config_forbidden | 2 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_26_stale_data_detection | 2 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_27_out_of_order_telemetry_handling | 2 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_28_failure_isolation_on_inference_exception | 4 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_29_four_parameter_alarm_compatibility | 8 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_30_actuator_simulation_compatibility | 1 | PASS (isolated dataset) |
| tests/Feature/FuzzyLogicInferenceTest.php | test_31_phase_1_to_14_regression_verification | 5 | PASS (isolated dataset) |
| tests/Feature/GoogleSheetsTelemetryAndSecurityTest.php | test_1_valid_google_sheets_rows_ingestion | 13 | PASS (isolated dataset) |
| tests/Feature/GoogleSheetsTelemetryAndSecurityTest.php | test_2_missing_optional_values_handled_gracefully | 7 | PASS (isolated dataset) |
| tests/Feature/GoogleSheetsTelemetryAndSecurityTest.php | test_3_invalid_numeric_values_rejected | 3 | PASS (isolated dataset) |
| tests/Feature/GoogleSheetsTelemetryAndSecurityTest.php | test_4_invalid_timestamps_rejected | 2 | PASS (isolated dataset) |
| tests/Feature/GoogleSheetsTelemetryAndSecurityTest.php | test_5_duplicate_rows_skipped_by_idempotency | 3 | PASS (isolated dataset) |
| tests/Feature/GoogleSheetsTelemetryAndSecurityTest.php | test_6_repeated_synchronization_does_not_duplicate | 3 | PASS (isolated dataset) |
| tests/Feature/GoogleSheetsTelemetryAndSecurityTest.php | test_7_out_of_order_readings_handled | 4 | PASS (isolated dataset) |
| tests/Feature/GoogleSheetsTelemetryAndSecurityTest.php | test_8_partial_failure_and_retry | 3 | PASS (isolated dataset) |
| tests/Feature/GoogleSheetsTelemetryAndSecurityTest.php | test_9_spreadsheet_unavailable_handled | 2 | PASS (isolated dataset) |
| tests/Feature/GoogleSheetsTelemetryAndSecurityTest.php | test_10_api_timeout_handled | 2 | PASS (isolated dataset) |
| tests/Feature/GoogleSheetsTelemetryAndSecurityTest.php | test_11_rate_limiting_handled | 2 | PASS (isolated dataset) |
| tests/Feature/GoogleSheetsTelemetryAndSecurityTest.php | test_12_device_token_valid | 3 | PASS (isolated dataset) |
| tests/Feature/GoogleSheetsTelemetryAndSecurityTest.php | test_13_device_token_invalid | 2 | PASS (isolated dataset) |
| tests/Feature/GoogleSheetsTelemetryAndSecurityTest.php | test_14_revoked_token_rejected | 2 | PASS (isolated dataset) |
| tests/Feature/GoogleSheetsTelemetryAndSecurityTest.php | test_15_missing_token_in_production | 2 | PASS (isolated dataset) |
| tests/Feature/GoogleSheetsTelemetryAndSecurityTest.php | test_16_simulator_compatibility | 2 | PASS (isolated dataset) |
| tests/Feature/GoogleSheetsTelemetryAndSecurityTest.php | test_17_four_parameter_alarm_policy | 3 | PASS (isolated dataset) |
| tests/Feature/GoogleSheetsTelemetryAndSecurityTest.php | test_18_dashboard_status_view_rendering | 4 | PASS (isolated dataset) |
| tests/Feature/GoogleSheetsTelemetryAndSecurityTest.php | test_19_rbac_access_control | 2 | PASS (isolated dataset) |
| tests/Feature/GoogleSheetsTelemetryAndSecurityTest.php | test_20_preservation_of_historical_data | 2 | PASS (isolated dataset) |
| tests/Feature/GoogleSheetsTelemetryAndSecurityTest.php | test_21_regression_phase_1_to_13 | 16 | PASS (isolated dataset) |
| tests/Feature/GoogleSheetsTelemetryAndSecurityTest.php | test_22_manual_sync_with_reset_cursor_resets_high_water_mark_safely | 4 | PASS (isolated dataset) |
| tests/Feature/MonitoringParameterExpansionTest.php | test_can_ingest_complete_eight_parameter_payload | 3 | PASS (isolated dataset) |
| tests/Feature/MonitoringParameterExpansionTest.php | test_can_ingest_legacy_payload_without_new_parameters | 7 | PASS (isolated dataset) |
| tests/Feature/MonitoringParameterExpansionTest.php | test_can_ingest_partial_sensor_payload | 7 | PASS (isolated dataset) |
| tests/Feature/MonitoringParameterExpansionTest.php | test_validates_physical_boundaries_for_all_parameters | 15 | PASS (isolated dataset) |
| tests/Feature/MonitoringParameterExpansionTest.php | test_api_statistics_includes_all_eight_parameters | 34 | PASS (isolated dataset) |
| tests/Feature/MonitoringParameterExpansionTest.php | test_api_dashboard_summary_includes_latest_sensor_readings | 13 | PASS (isolated dataset) |
| tests/Feature/MonitoringParameterExpansionTest.php | test_admin_can_create_and_update_monitoring_with_new_parameters_via_web | 9 | PASS (isolated dataset) |
| tests/Feature/MonitoringParameterExpansionTest.php | test_pengamat_access_control_for_monitoring_web_routes | 3 | PASS (isolated dataset) |
| tests/Feature/MonitoringParameterExpansionTest.php | test_dashboard_web_view_renders_all_sensor_charts | 9 | PASS (isolated dataset) |
| tests/Feature/MonitoringParameterExpansionTest.php | test_monitoring_show_view_displays_expanded_parameters | 7 | PASS (isolated dataset) |
| tests/Feature/MonitoringReportTest.php | test_01_guest_cannot_access_monitoring_reports_redirects_to_login | 2 | PASS (isolated dataset) |
| tests/Feature/MonitoringReportTest.php | test_02_guest_cannot_export_csv_redirects_to_login | 2 | PASS (isolated dataset) |
| tests/Feature/MonitoringReportTest.php | test_03_guest_cannot_export_pdf_redirects_to_login | 2 | PASS (isolated dataset) |
| tests/Feature/MonitoringReportTest.php | test_04_pengamat_can_access_monitoring_report_page | 6 | PASS (isolated dataset) |
| tests/Feature/MonitoringReportTest.php | test_05_admin_can_access_monitoring_report_page | 3 | PASS (isolated dataset) |
| tests/Feature/MonitoringReportTest.php | test_06_report_page_renders_8_sensor_parameters_in_statistics | 9 | PASS (isolated dataset) |
| tests/Feature/MonitoringReportTest.php | test_07_report_page_renders_alarm_summary_and_fuzzy_summary | 6 | PASS (isolated dataset) |
| tests/Feature/MonitoringReportTest.php | test_08_filter_by_date_range_filters_records_accurately | 3 | PASS (isolated dataset) |
| tests/Feature/MonitoringReportTest.php | test_09_filter_by_plant_filters_records_to_selected_plant | 5 | PASS (isolated dataset) |
| tests/Feature/MonitoringReportTest.php | test_10_filter_by_data_source_filters_records | 3 | PASS (isolated dataset) |
| tests/Feature/MonitoringReportTest.php | test_11_filter_by_plant_condition_filters_records | 3 | PASS (isolated dataset) |
| tests/Feature/MonitoringReportTest.php | test_12_validation_rejects_invalid_date_range_where_to_is_before_from | 2 | PASS (isolated dataset) |
| tests/Feature/MonitoringReportTest.php | test_13_validation_rejects_non_existent_plant_id | 2 | PASS (isolated dataset) |
| tests/Feature/MonitoringReportTest.php | test_14_statistics_service_calculates_avg_min_max_without_treating_null_as_zero | 6 | PASS (isolated dataset) |
| tests/Feature/MonitoringReportTest.php | test_15_statistics_service_handles_empty_dataset_gracefully | 7 | PASS (isolated dataset) |
| tests/Feature/MonitoringReportTest.php | test_16_csv_export_returns_streamed_response_with_utf8_bom_and_headers | 10 | PASS (isolated dataset) |
| tests/Feature/MonitoringReportTest.php | test_17_csv_export_sanitizes_against_formula_injection | 5 | PASS (isolated dataset) |
| tests/Feature/MonitoringReportTest.php | test_18_csv_export_respects_active_filters | 2 | PASS (isolated dataset) |
| tests/Feature/MonitoringReportTest.php | test_19_pdf_export_returns_successful_pdf_download_response | 3 | PASS (isolated dataset) |
| tests/Feature/MonitoringReportTest.php | test_20_pdf_export_renders_academic_a4_layout_with_populated_data | 2 | PASS (isolated dataset) |
| tests/Feature/MonitoringReportTest.php | test_21_sorting_and_pagination_work_on_reports | 2 | PASS (isolated dataset) |
| tests/Feature/MonitoringReportTest.php | test_22_dashboard_renders_all_sections_a_through_g | 22 | PASS (isolated dataset) |
| tests/Feature/MonitoringReportTest.php | test_23_preservation_of_historical_data_across_report_generation | 3 | PASS (isolated dataset) |
| tests/Feature/MonitoringReportTest.php | test_24_pdf_500_record_limit_notice_is_explicitly_displayed_when_data_exceeds_threshold | 4 | PASS (isolated dataset) |
| tests/Feature/MonitoringTest.php | test_guest_is_redirected_to_login_when_accessing_monitoring_routes | 14 | PASS (isolated dataset) |
| tests/Feature/MonitoringTest.php | test_pengamat_can_view_monitoring_index | 6 | PASS (isolated dataset) |
| tests/Feature/MonitoringTest.php | test_admin_can_view_monitoring_index_with_action_buttons | 5 | PASS (isolated dataset) |
| tests/Feature/MonitoringTest.php | test_monitoring_index_can_filter_by_plant | 2 | PASS (isolated dataset) |
| tests/Feature/MonitoringTest.php | test_monitoring_index_can_filter_by_condition | 1 | PASS (isolated dataset) |
| tests/Feature/MonitoringTest.php | test_monitoring_index_can_search_by_keyword | 2 | PASS (isolated dataset) |
| tests/Feature/MonitoringTest.php | test_monitoring_index_has_pagination | 2 | PASS (isolated dataset) |
| tests/Feature/MonitoringTest.php | test_pengamat_can_view_monitoring_detail | 6 | PASS (isolated dataset) |
| tests/Feature/MonitoringTest.php | test_admin_can_view_monitoring_detail_with_management_buttons | 4 | PASS (isolated dataset) |
| tests/Feature/MonitoringTest.php | test_pengamat_can_access_create_form | 3 | PASS (isolated dataset) |
| tests/Feature/MonitoringTest.php | test_pengamat_can_store_valid_monitoring_data | 4 | PASS (isolated dataset) |
| tests/Feature/MonitoringTest.php | test_admin_can_store_valid_monitoring_data | 4 | PASS (isolated dataset) |
| tests/Feature/MonitoringTest.php | test_store_validation_errors_for_missing_required_fields | 8 | PASS (isolated dataset) |
| tests/Feature/MonitoringTest.php | test_store_validation_errors_for_invalid_numerical_ranges | 4 | PASS (isolated dataset) |
| tests/Feature/MonitoringTest.php | test_admin_can_access_edit_form | 3 | PASS (isolated dataset) |
| tests/Feature/MonitoringTest.php | test_pengamat_cannot_access_edit_form | 1 | PASS (isolated dataset) |
| tests/Feature/MonitoringTest.php | test_admin_can_update_monitoring_data | 4 | PASS (isolated dataset) |
| tests/Feature/MonitoringTest.php | test_pengamat_cannot_update_monitoring_data | 2 | PASS (isolated dataset) |
| tests/Feature/MonitoringTest.php | test_admin_can_delete_monitoring_data | 4 | PASS (isolated dataset) |
| tests/Feature/MonitoringTest.php | test_pengamat_cannot_delete_monitoring_data | 2 | PASS (isolated dataset) |
| tests/Feature/PlantTest.php | test_guest_cannot_access_plant_routes | 14 | PASS (isolated dataset) |
| tests/Feature/PlantTest.php | test_pengamat_can_view_plants | 4 | PASS (isolated dataset) |
| tests/Feature/PlantTest.php | test_pengamat_can_view_plant_detail | 5 | PASS (isolated dataset) |
| tests/Feature/PlantTest.php | test_pengamat_cannot_create_plant | 1 | PASS (isolated dataset) |
| tests/Feature/PlantTest.php | test_pengamat_cannot_store_plant | 2 | PASS (isolated dataset) |
| tests/Feature/PlantTest.php | test_pengamat_cannot_edit_plant | 1 | PASS (isolated dataset) |
| tests/Feature/PlantTest.php | test_pengamat_cannot_update_plant | 2 | PASS (isolated dataset) |
| tests/Feature/PlantTest.php | test_pengamat_cannot_delete_plant | 2 | PASS (isolated dataset) |
| tests/Feature/PlantTest.php | test_admin_can_view_plants_with_management_controls | 3 | PASS (isolated dataset) |
| tests/Feature/PlantTest.php | test_admin_can_view_plant_detail | 3 | PASS (isolated dataset) |
| tests/Feature/PlantTest.php | test_admin_can_open_create_form | 3 | PASS (isolated dataset) |
| tests/Feature/PlantTest.php | test_admin_can_create_plant | 4 | PASS (isolated dataset) |
| tests/Feature/PlantTest.php | test_admin_can_open_edit_form | 3 | PASS (isolated dataset) |
| tests/Feature/PlantTest.php | test_admin_can_update_plant | 4 | PASS (isolated dataset) |
| tests/Feature/PlantTest.php | test_admin_can_delete_plant_without_monitoring | 4 | PASS (isolated dataset) |
| tests/Feature/PlantTest.php | test_invalid_plant_data_rejected | 4 | PASS (isolated dataset) |
| tests/Feature/PlantTest.php | test_plant_search_works | 2 | PASS (isolated dataset) |
| tests/Feature/PlantTest.php | test_plant_filter_by_status_works | 2 | PASS (isolated dataset) |
| tests/Feature/PlantTest.php | test_plant_pagination_works | 2 | PASS (isolated dataset) |
| tests/Feature/PlantTest.php | test_plant_shows_monitoring_count | 2 | PASS (isolated dataset) |
| tests/Feature/PlantTest.php | test_plant_with_monitoring_cannot_be_deleted | 4 | PASS (isolated dataset) |


## Pemeriksaan file dan oracle akhir

CSV aktual berisi 23 kolom dan 959 record (sama database), 0 malformed rows, 0 prefix formula tanpa perlindungan, dan 1.900 sel formula diawali apostrof. Count serta rata-rata seluruh delapan sensor cocok database, termasuk null yang diabaikan (csv-check.json). Oracle fuzzy independen 12/12 cocok (fuzzy-oracle.json). PDF valid 23 halaman dan 500 baris, tetapi footer nomor halaman hilang serta legenda kategori salah; response 200 bukan PASS seluruh requirement PDF. Durasi PDF sekitar 36,35 detik pada single-user local run, bukan benchmark production.
