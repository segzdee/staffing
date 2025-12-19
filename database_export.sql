Warning: A partial dump from a server that has GTIDs will by default include the GTIDs of all transactions, even those that changed suppressed parts of the database. If you don't want to restore GTIDs, pass --set-gtid-purged=OFF. To make a complete dump, pass --all-databases --triggers --routines --events. 
Warning: A dump from a server that has GTIDs enabled will by default include the GTIDs of all transactions, even those that were executed during its extraction and might not be represented in the dumped data. This might result in an inconsistent data dump. 
In order to ensure a consistent backup of the database, pass --single-transaction or --lock-all-tables or --source-data. 
-- MySQL dump 10.13  Distrib 9.5.0, for macos26.1 (arm64)
--
-- Host: localhost    Database: overtimestaff
-- ------------------------------------------------------
-- Server version	9.5.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
SET @MYSQLDUMP_TEMP_LOG_BIN = @@SESSION.SQL_LOG_BIN;
SET @@SESSION.SQL_LOG_BIN= 0;

--
-- GTID state at the beginning of the backup 
--

SET @@GLOBAL.GTID_PURGED=/*!80000 '+'*/ '43caa132-d8e9-11f0-b0cb-b434e6e5c93e:1-1129414';

--
-- Table structure for table `adjudication_cases`
--

DROP TABLE IF EXISTS `adjudication_cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `adjudication_cases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `background_check_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `case_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `case_type` enum('criminal_record','identity_mismatch','employment_discrepancy','education_discrepancy','motor_vehicle','sex_offender','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('open','under_review','pending_worker_response','pre_adverse_action','waiting_period','final_review','approved','adverse_action','closed','escalated') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `assigned_to` bigint unsigned DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `escalated_to` bigint unsigned DEFAULT NULL,
  `escalated_at` timestamp NULL DEFAULT NULL,
  `findings_encrypted` text COLLATE utf8mb4_unicode_ci,
  `record_details` json DEFAULT NULL,
  `severity` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `worker_response` text COLLATE utf8mb4_unicode_ci,
  `worker_documents` json DEFAULT NULL,
  `worker_responded_at` timestamp NULL DEFAULT NULL,
  `decision` enum('pending','approved','conditionally_approved','denied') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `decision_rationale` text COLLATE utf8mb4_unicode_ci,
  `decided_by` bigint unsigned DEFAULT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `conditions` json DEFAULT NULL,
  `review_date` date DEFAULT NULL,
  `pre_adverse_notice_sent_at` timestamp NULL DEFAULT NULL,
  `waiting_period_ends_at` date DEFAULT NULL,
  `final_notice_sent_at` timestamp NULL DEFAULT NULL,
  `communications_log` json DEFAULT NULL,
  `sla_deadline` timestamp NULL DEFAULT NULL,
  `sla_breached` tinyint(1) NOT NULL DEFAULT '0',
  `audit_log` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `adjudication_cases_case_number_unique` (`case_number`),
  KEY `adjudication_cases_background_check_id_foreign` (`background_check_id`),
  KEY `adjudication_cases_assigned_to_foreign` (`assigned_to`),
  KEY `adjudication_cases_escalated_to_foreign` (`escalated_to`),
  KEY `adjudication_cases_decided_by_foreign` (`decided_by`),
  KEY `adjudication_cases_status_assigned_to_index` (`status`,`assigned_to`),
  KEY `adjudication_cases_user_id_status_index` (`user_id`,`status`),
  KEY `adjudication_cases_sla_deadline_status_index` (`sla_deadline`,`status`),
  CONSTRAINT `adjudication_cases_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
  CONSTRAINT `adjudication_cases_background_check_id_foreign` FOREIGN KEY (`background_check_id`) REFERENCES `background_checks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `adjudication_cases_decided_by_foreign` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`),
  CONSTRAINT `adjudication_cases_escalated_to_foreign` FOREIGN KEY (`escalated_to`) REFERENCES `users` (`id`),
  CONSTRAINT `adjudication_cases_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `adjudication_cases`
--

LOCK TABLES `adjudication_cases` WRITE;
/*!40000 ALTER TABLE `adjudication_cases` DISABLE KEYS */;
/*!40000 ALTER TABLE `adjudication_cases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_dispute_queue`
--

DROP TABLE IF EXISTS `admin_dispute_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_dispute_queue` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shift_payment_id` bigint unsigned NOT NULL,
  `filed_by` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `worker_id` bigint unsigned NOT NULL,
  `business_id` bigint unsigned NOT NULL,
  `status` enum('pending','investigating','evidence_review','resolved','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `escalation_level` tinyint NOT NULL DEFAULT '0',
  `dispute_reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `evidence_urls` json DEFAULT NULL,
  `assigned_to_admin` bigint unsigned DEFAULT NULL,
  `previous_assigned_admin` bigint unsigned DEFAULT NULL,
  `resolution_notes` text COLLATE utf8mb4_unicode_ci,
  `internal_notes` text COLLATE utf8mb4_unicode_ci,
  `resolution_outcome` enum('worker_favor','business_favor','split','no_fault') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adjustment_amount` decimal(10,2) DEFAULT NULL,
  `filed_at` timestamp NOT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `escalated_at` timestamp NULL DEFAULT NULL,
  `sla_warning_sent_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `admin_dispute_queue_status_filed_at_index` (`status`,`filed_at`),
  KEY `admin_dispute_queue_assigned_to_admin_index` (`assigned_to_admin`),
  KEY `admin_dispute_queue_priority_index` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_dispute_queue`
--

LOCK TABLES `admin_dispute_queue` WRITE;
/*!40000 ALTER TABLE `admin_dispute_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_dispute_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_settings`
--

DROP TABLE IF EXISTS `admin_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `file_size_allowed` bigint NOT NULL DEFAULT '10240',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_settings`
--

LOCK TABLES `admin_settings` WRITE;
/*!40000 ALTER TABLE `admin_settings` DISABLE KEYS */;
INSERT INTO `admin_settings` VALUES (1,10240,'2025-12-18 16:52:06','2025-12-18 16:52:06');
/*!40000 ALTER TABLE `admin_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agency_applications`
--

DROP TABLE IF EXISTS `agency_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agency_applications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `status` enum('draft','submitted','document_review','document_approved','document_rejected','compliance_review','compliance_approved','compliance_rejected','commercial_review','commercial_approved','commercial_rejected','worker_onboarding','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `partnership_tier` enum('standard','professional','enterprise') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'standard',
  `business_info` json DEFAULT NULL COMMENT 'Company name, registration number, tax ID, founding date, employee count, etc.',
  `contact_info` json DEFAULT NULL COMMENT 'Primary contact, billing contact, operations contact, etc.',
  `application_reference` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewer_id` bigint unsigned DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `rejection_date` timestamp NULL DEFAULT NULL,
  `can_reapply` tinyint(1) NOT NULL DEFAULT '1',
  `reapply_after` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `document_review_completed_at` timestamp NULL DEFAULT NULL,
  `compliance_review_completed_at` timestamp NULL DEFAULT NULL,
  `commercial_review_completed_at` timestamp NULL DEFAULT NULL,
  `worker_onboarding_completed_at` timestamp NULL DEFAULT NULL,
  `internal_notes` text COLLATE utf8mb4_unicode_ci,
  `priority` enum('low','normal','high','urgent') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `referral_source` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referral_code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `submission_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `submission_user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `agency_applications_application_reference_unique` (`application_reference`),
  KEY `agency_applications_user_id_foreign` (`user_id`),
  KEY `agency_applications_approved_by_foreign` (`approved_by`),
  KEY `agency_applications_status_index` (`status`),
  KEY `agency_applications_partnership_tier_index` (`partnership_tier`),
  KEY `agency_applications_application_reference_index` (`application_reference`),
  KEY `agency_applications_submitted_at_index` (`submitted_at`),
  KEY `agency_applications_reviewer_id_index` (`reviewer_id`),
  KEY `agency_applications_status_partnership_tier_index` (`status`,`partnership_tier`),
  KEY `agency_applications_status_submitted_at_index` (`status`,`submitted_at`),
  KEY `agency_applications_priority_index` (`priority`),
  CONSTRAINT `agency_applications_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `agency_applications_reviewer_id_foreign` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `agency_applications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agency_applications`
--

LOCK TABLES `agency_applications` WRITE;
/*!40000 ALTER TABLE `agency_applications` DISABLE KEYS */;
/*!40000 ALTER TABLE `agency_applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agency_clients`
--

DROP TABLE IF EXISTS `agency_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agency_clients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `agency_id` bigint unsigned NOT NULL,
  `company_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `industry` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_markup_percent` decimal(5,2) NOT NULL DEFAULT '15.00',
  `status` enum('active','inactive','pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `agency_clients_agency_id_status_index` (`agency_id`,`status`),
  CONSTRAINT `agency_clients_agency_id_foreign` FOREIGN KEY (`agency_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agency_clients`
--

LOCK TABLES `agency_clients` WRITE;
/*!40000 ALTER TABLE `agency_clients` DISABLE KEYS */;
/*!40000 ALTER TABLE `agency_clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agency_commercial_agreements`
--

DROP TABLE IF EXISTS `agency_commercial_agreements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agency_commercial_agreements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint unsigned NOT NULL,
  `agreement_type` enum('master_service_agreement','commission_schedule','data_processing','non_disclosure','service_level','payment_terms','insurance_requirement','code_of_conduct','worker_protection','amendment') COLLATE utf8mb4_unicode_ci NOT NULL,
  `commission_rate` decimal(5,2) DEFAULT NULL,
  `tiered_commission` json DEFAULT NULL COMMENT 'Volume-based commission tiers',
  `special_rates` json DEFAULT NULL COMMENT 'Special rates for specific industries/shifts',
  `contract_terms` json DEFAULT NULL COMMENT 'Full contract terms and conditions',
  `version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1.0',
  `replaces_agreement_id` bigint unsigned DEFAULT NULL,
  `agreement_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agreement_disk` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 's3',
  `agreement_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','pending','signed','expired','terminated','superseded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `sent_for_signature_at` timestamp NULL DEFAULT NULL,
  `signed_at` timestamp NULL DEFAULT NULL,
  `signature_data` json DEFAULT NULL COMMENT 'E-signature details: signer info, IP, device, signature image ref',
  `signer_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signer_email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signer_title` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signing_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signing_user_agent` text COLLATE utf8mb4_unicode_ci,
  `esign_provider` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `esign_envelope_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `esign_document_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `esign_metadata` json DEFAULT NULL,
  `countersigned_by` bigint unsigned DEFAULT NULL,
  `countersigned_at` timestamp NULL DEFAULT NULL,
  `countersigner_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `countersigner_title` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `auto_renew` tinyint(1) NOT NULL DEFAULT '0',
  `renewal_notice_days` int NOT NULL DEFAULT '30',
  `renewal_reminder_sent_at` timestamp NULL DEFAULT NULL,
  `terminated_at` timestamp NULL DEFAULT NULL,
  `terminated_by` bigint unsigned DEFAULT NULL,
  `termination_reason` text COLLATE utf8mb4_unicode_ci,
  `internal_notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `agency_commercial_agreements_replaces_agreement_id_foreign` (`replaces_agreement_id`),
  KEY `agency_commercial_agreements_countersigned_by_foreign` (`countersigned_by`),
  KEY `agency_commercial_agreements_terminated_by_foreign` (`terminated_by`),
  KEY `agency_commercial_agreements_created_by_foreign` (`created_by`),
  KEY `agency_commercial_agreements_application_id_index` (`application_id`),
  KEY `agency_commercial_agreements_agreement_type_index` (`agreement_type`),
  KEY `agency_commercial_agreements_status_index` (`status`),
  KEY `agency_commercial_agreements_signed_at_index` (`signed_at`),
  KEY `agency_commercial_agreements_expiry_date_index` (`expiry_date`),
  KEY `agency_commercial_agreements_application_id_agreement_type_index` (`application_id`,`agreement_type`),
  KEY `agency_commercial_agreements_application_id_status_index` (`application_id`,`status`),
  KEY `agency_commercial_agreements_esign_envelope_id_index` (`esign_envelope_id`),
  KEY `agency_commercial_agreements_document_hash_index` (`document_hash`),
  CONSTRAINT `agency_commercial_agreements_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `agency_applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `agency_commercial_agreements_countersigned_by_foreign` FOREIGN KEY (`countersigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `agency_commercial_agreements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `agency_commercial_agreements_replaces_agreement_id_foreign` FOREIGN KEY (`replaces_agreement_id`) REFERENCES `agency_commercial_agreements` (`id`) ON DELETE SET NULL,
  CONSTRAINT `agency_commercial_agreements_terminated_by_foreign` FOREIGN KEY (`terminated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agency_commercial_agreements`
--

LOCK TABLES `agency_commercial_agreements` WRITE;
/*!40000 ALTER TABLE `agency_commercial_agreements` DISABLE KEYS */;
/*!40000 ALTER TABLE `agency_commercial_agreements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agency_compliance_checks`
--

DROP TABLE IF EXISTS `agency_compliance_checks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agency_compliance_checks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint unsigned NOT NULL,
  `check_type` enum('business_license','insurance_coverage','tax_compliance','background_check','reference_verification','financial_stability','legal_standing','regulatory_compliance','data_protection','employment_law','health_safety','anti_money_laundering','sanctions_screening') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','in_progress','passed','failed','waived','expired','not_required') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `checked_by` bigint unsigned DEFAULT NULL,
  `checked_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `findings` text COLLATE utf8mb4_unicode_ci,
  `failure_reason` text COLLATE utf8mb4_unicode_ci,
  `waiver_justification` text COLLATE utf8mb4_unicode_ci,
  `external_provider` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_reference_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_result` json DEFAULT NULL,
  `external_checked_at` timestamp NULL DEFAULT NULL,
  `risk_level` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `risk_notes` text COLLATE utf8mb4_unicode_ci,
  `valid_until` date DEFAULT NULL,
  `renewal_required` tinyint(1) NOT NULL DEFAULT '0',
  `renewal_reminder_sent_at` timestamp NULL DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `minimum_score_required` decimal(5,2) DEFAULT NULL,
  `supporting_document_ids` json DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `due_by` timestamp NULL DEFAULT NULL,
  `sla_breached` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_application_check_type` (`application_id`,`check_type`),
  KEY `agency_compliance_checks_checked_by_foreign` (`checked_by`),
  KEY `agency_compliance_checks_application_id_index` (`application_id`),
  KEY `agency_compliance_checks_check_type_index` (`check_type`),
  KEY `agency_compliance_checks_status_index` (`status`),
  KEY `agency_compliance_checks_application_id_check_type_index` (`application_id`,`check_type`),
  KEY `agency_compliance_checks_application_id_status_index` (`application_id`,`status`),
  KEY `agency_compliance_checks_valid_until_index` (`valid_until`),
  KEY `agency_compliance_checks_risk_level_index` (`risk_level`),
  KEY `agency_compliance_checks_external_reference_id_index` (`external_reference_id`),
  CONSTRAINT `agency_compliance_checks_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `agency_applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `agency_compliance_checks_checked_by_foreign` FOREIGN KEY (`checked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agency_compliance_checks`
--

LOCK TABLES `agency_compliance_checks` WRITE;
/*!40000 ALTER TABLE `agency_compliance_checks` DISABLE KEYS */;
/*!40000 ALTER TABLE `agency_compliance_checks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agency_documents`
--

DROP TABLE IF EXISTS `agency_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agency_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint unsigned NOT NULL,
  `document_type` enum('business_license','insurance_cert','tax_id','company_registration','references','bank_statement','proof_of_address','director_id','vat_certificate','industry_certification','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint unsigned DEFAULT NULL,
  `disk` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 's3',
  `document_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `issuing_authority` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issuing_country` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verification_status` enum('pending','verified','rejected','expired','superseded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `verified_by` bigint unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `verification_notes` text COLLATE utf8mb4_unicode_ci,
  `auto_verified` tinyint(1) NOT NULL DEFAULT '0',
  `auto_verification_provider` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `auto_verification_result` json DEFAULT NULL,
  `auto_verification_confidence` decimal(5,2) DEFAULT NULL,
  `version` int unsigned NOT NULL DEFAULT '1',
  `replaces_document_id` bigint unsigned DEFAULT NULL,
  `file_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `agency_documents_verified_by_foreign` (`verified_by`),
  KEY `agency_documents_replaces_document_id_foreign` (`replaces_document_id`),
  KEY `agency_documents_application_id_index` (`application_id`),
  KEY `agency_documents_document_type_index` (`document_type`),
  KEY `agency_documents_verification_status_index` (`verification_status`),
  KEY `agency_documents_expiry_date_index` (`expiry_date`),
  KEY `agency_documents_application_id_document_type_index` (`application_id`,`document_type`),
  KEY `agency_documents_application_id_verification_status_index` (`application_id`,`verification_status`),
  KEY `agency_documents_file_hash_index` (`file_hash`),
  CONSTRAINT `agency_documents_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `agency_applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `agency_documents_replaces_document_id_foreign` FOREIGN KEY (`replaces_document_id`) REFERENCES `agency_documents` (`id`) ON DELETE SET NULL,
  CONSTRAINT `agency_documents_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agency_documents`
--

LOCK TABLES `agency_documents` WRITE;
/*!40000 ALTER TABLE `agency_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `agency_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agency_invitations`
--

DROP TABLE IF EXISTS `agency_invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agency_invitations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `agency_id` bigint unsigned NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('email','phone','link','bulk') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email',
  `status` enum('pending','sent','viewed','accepted','expired','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `expires_at` timestamp NOT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `viewed_at` timestamp NULL DEFAULT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `accepted_by_user_id` bigint unsigned DEFAULT NULL,
  `preset_commission_rate` decimal(5,2) DEFAULT NULL,
  `preset_skills` json DEFAULT NULL,
  `preset_certifications` json DEFAULT NULL,
  `personal_message` text COLLATE utf8mb4_unicode_ci,
  `batch_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invitation_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accepted_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accepted_user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `agency_invitations_token_unique` (`token`),
  KEY `agency_invitations_accepted_by_user_id_foreign` (`accepted_by_user_id`),
  KEY `agency_invitations_token_index` (`token`),
  KEY `agency_invitations_email_index` (`email`),
  KEY `agency_invitations_phone_index` (`phone`),
  KEY `agency_invitations_status_index` (`status`),
  KEY `agency_invitations_expires_at_index` (`expires_at`),
  KEY `agency_invitations_agency_id_status_index` (`agency_id`,`status`),
  KEY `agency_invitations_batch_id_index` (`batch_id`),
  CONSTRAINT `agency_invitations_accepted_by_user_id_foreign` FOREIGN KEY (`accepted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `agency_invitations_agency_id_foreign` FOREIGN KEY (`agency_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agency_invitations`
--

LOCK TABLES `agency_invitations` WRITE;
/*!40000 ALTER TABLE `agency_invitations` DISABLE KEYS */;
/*!40000 ALTER TABLE `agency_invitations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agency_performance_notifications`
--

DROP TABLE IF EXISTS `agency_performance_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agency_performance_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `agency_id` bigint unsigned NOT NULL,
  `scorecard_id` bigint unsigned DEFAULT NULL,
  `notification_type` enum('yellow_warning','red_alert','fee_increase','suspension','improvement','escalation','admin_review') COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` enum('info','warning','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'warning',
  `status_at_notification` enum('green','yellow','red') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `previous_status` enum('green','yellow','red') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `metrics_snapshot` json DEFAULT NULL,
  `action_items` json DEFAULT NULL,
  `improvement_deadline` date DEFAULT NULL,
  `consecutive_yellow_weeks` int NOT NULL DEFAULT '0',
  `consecutive_red_weeks` int NOT NULL DEFAULT '0',
  `previous_commission_rate` decimal(5,2) DEFAULT NULL,
  `new_commission_rate` decimal(5,2) DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `sent_via` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email',
  `email_delivered` tinyint(1) NOT NULL DEFAULT '0',
  `email_delivered_at` timestamp NULL DEFAULT NULL,
  `requires_acknowledgment` tinyint(1) NOT NULL DEFAULT '1',
  `acknowledged` tinyint(1) NOT NULL DEFAULT '0',
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `acknowledged_by` bigint unsigned DEFAULT NULL,
  `acknowledgment_notes` text COLLATE utf8mb4_unicode_ci,
  `escalated` tinyint(1) NOT NULL DEFAULT '0',
  `escalated_at` timestamp NULL DEFAULT NULL,
  `escalated_to` bigint unsigned DEFAULT NULL,
  `escalation_reason` text COLLATE utf8mb4_unicode_ci,
  `escalation_level` int NOT NULL DEFAULT '0',
  `escalation_due_at` timestamp NULL DEFAULT NULL,
  `admin_reviewed` tinyint(1) NOT NULL DEFAULT '0',
  `admin_reviewed_at` timestamp NULL DEFAULT NULL,
  `admin_reviewed_by` bigint unsigned DEFAULT NULL,
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `admin_decision` enum('pending','uphold','reduce','dismiss') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `appealed` tinyint(1) NOT NULL DEFAULT '0',
  `appealed_at` timestamp NULL DEFAULT NULL,
  `appeal_reason` text COLLATE utf8mb4_unicode_ci,
  `appeal_status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `appeal_response` text COLLATE utf8mb4_unicode_ci,
  `appeal_resolved_at` timestamp NULL DEFAULT NULL,
  `follow_up_count` int NOT NULL DEFAULT '0',
  `last_follow_up_at` timestamp NULL DEFAULT NULL,
  `next_follow_up_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `agency_performance_notifications_scorecard_id_foreign` (`scorecard_id`),
  KEY `agency_performance_notifications_acknowledged_by_foreign` (`acknowledged_by`),
  KEY `agency_performance_notifications_escalated_to_foreign` (`escalated_to`),
  KEY `agency_performance_notifications_admin_reviewed_by_foreign` (`admin_reviewed_by`),
  KEY `apn_agency_type_idx` (`agency_id`,`notification_type`),
  KEY `apn_agency_created_idx` (`agency_id`,`created_at`),
  KEY `apn_type_ack_idx` (`notification_type`,`acknowledged`),
  KEY `apn_escalation_idx` (`escalated`,`escalation_due_at`),
  KEY `apn_ack_status_idx` (`requires_acknowledgment`,`acknowledged`,`escalated`),
  KEY `apn_severity_idx` (`severity`),
  CONSTRAINT `agency_performance_notifications_acknowledged_by_foreign` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `agency_performance_notifications_admin_reviewed_by_foreign` FOREIGN KEY (`admin_reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `agency_performance_notifications_agency_id_foreign` FOREIGN KEY (`agency_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `agency_performance_notifications_escalated_to_foreign` FOREIGN KEY (`escalated_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `agency_performance_notifications_scorecard_id_foreign` FOREIGN KEY (`scorecard_id`) REFERENCES `agency_performance_scorecards` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agency_performance_notifications`
--

LOCK TABLES `agency_performance_notifications` WRITE;
/*!40000 ALTER TABLE `agency_performance_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `agency_performance_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agency_performance_scorecards`
--

DROP TABLE IF EXISTS `agency_performance_scorecards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agency_performance_scorecards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `agency_id` bigint unsigned NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `period_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'weekly',
  `fill_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `no_show_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `average_worker_rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `complaint_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `total_shifts_assigned` int NOT NULL DEFAULT '0',
  `shifts_filled` int NOT NULL DEFAULT '0',
  `shifts_unfilled` int NOT NULL DEFAULT '0',
  `no_shows` int NOT NULL DEFAULT '0',
  `complaints_received` int NOT NULL DEFAULT '0',
  `total_ratings` int NOT NULL DEFAULT '0',
  `total_rating_sum` decimal(8,2) NOT NULL DEFAULT '0.00',
  `urgent_fill_requests` int NOT NULL DEFAULT '0',
  `urgent_fills_completed` int NOT NULL DEFAULT '0',
  `urgent_fill_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `average_response_time_minutes` decimal(8,2) DEFAULT NULL,
  `status` enum('green','yellow','red') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'green',
  `warnings` json DEFAULT NULL,
  `flags` json DEFAULT NULL,
  `target_fill_rate` decimal(5,2) NOT NULL DEFAULT '90.00',
  `target_no_show_rate` decimal(5,2) NOT NULL DEFAULT '3.00',
  `target_average_rating` decimal(3,2) NOT NULL DEFAULT '4.30',
  `target_complaint_rate` decimal(5,2) NOT NULL DEFAULT '2.00',
  `warning_sent` tinyint(1) NOT NULL DEFAULT '0',
  `warning_sent_at` timestamp NULL DEFAULT NULL,
  `sanction_applied` tinyint(1) NOT NULL DEFAULT '0',
  `sanction_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sanction_applied_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `generated_at` timestamp NULL DEFAULT NULL,
  `generated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `agency_performance_scorecards_agency_id_index` (`agency_id`),
  KEY `agency_performance_scorecards_period_start_period_end_index` (`period_start`,`period_end`),
  KEY `agency_performance_scorecards_status_index` (`status`),
  KEY `agency_performance_scorecards_generated_at_index` (`generated_at`),
  KEY `agency_period_idx` (`agency_id`,`period_start`,`period_end`),
  KEY `agency_performance_scorecards_generated_by_foreign` (`generated_by`),
  CONSTRAINT `agency_performance_scorecards_agency_id_foreign` FOREIGN KEY (`agency_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `agency_performance_scorecards_generated_by_foreign` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agency_performance_scorecards`
--

LOCK TABLES `agency_performance_scorecards` WRITE;
/*!40000 ALTER TABLE `agency_performance_scorecards` DISABLE KEYS */;
/*!40000 ALTER TABLE `agency_performance_scorecards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agency_profiles`
--

DROP TABLE IF EXISTS `agency_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agency_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `agency_tier_id` bigint unsigned DEFAULT NULL,
  `tier_achieved_at` timestamp NULL DEFAULT NULL,
  `tier_review_at` timestamp NULL DEFAULT NULL,
  `tier_metrics_snapshot` json DEFAULT NULL,
  `onboarding_completed` tinyint(1) NOT NULL DEFAULT '0',
  `onboarding_step` int DEFAULT NULL,
  `onboarding_completed_at` timestamp NULL DEFAULT NULL,
  `agency_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `license_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_verified` tinyint(1) NOT NULL DEFAULT '0',
  `license_verified_at` timestamp NULL DEFAULT NULL,
  `license_expires_at` date DEFAULT NULL,
  `tax_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_verified` tinyint(1) NOT NULL DEFAULT '0',
  `tax_verified_at` timestamp NULL DEFAULT NULL,
  `background_check_status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `background_check_passed` tinyint(1) NOT NULL DEFAULT '0',
  `background_check_initiated_at` timestamp NULL DEFAULT NULL,
  `background_check_completed_at` timestamp NULL DEFAULT NULL,
  `references` json DEFAULT NULL,
  `agreement_signed` tinyint(1) NOT NULL DEFAULT '0',
  `agreement_signed_at` timestamp NULL DEFAULT NULL,
  `agreement_version` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agreement_signer_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agreement_signer_title` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agreement_signer_ip` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `test_shift_completed` tinyint(1) NOT NULL DEFAULT '0',
  `test_shift_id` bigint unsigned DEFAULT NULL,
  `manual_verifications` json DEFAULT NULL,
  `verification_status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `verification_notes` text COLLATE utf8mb4_unicode_ci,
  `business_model` enum('staffing_agency','temp_agency','consulting') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'staffing_agency',
  `commission_rate` decimal(5,2) NOT NULL DEFAULT '10.00',
  `variable_commission_rate` decimal(5,2) DEFAULT NULL,
  `total_commission_earned` decimal(12,2) NOT NULL DEFAULT '0.00',
  `pending_commission` decimal(12,2) NOT NULL DEFAULT '0.00',
  `paid_commission` decimal(12,2) NOT NULL DEFAULT '0.00',
  `stripe_connect_account_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_onboarding_complete` tinyint(1) NOT NULL DEFAULT '0',
  `stripe_onboarded_at` timestamp NULL DEFAULT NULL,
  `stripe_payout_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `stripe_account_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_charges_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `stripe_details_submitted` tinyint(1) NOT NULL DEFAULT '0',
  `stripe_requirements` json DEFAULT NULL,
  `stripe_default_currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `last_payout_at` timestamp NULL DEFAULT NULL,
  `last_payout_amount` decimal(12,2) DEFAULT NULL,
  `last_payout_status` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_payouts_count` int NOT NULL DEFAULT '0',
  `total_payouts_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `urgent_fill_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `urgent_fill_commission_multiplier` decimal(3,2) NOT NULL DEFAULT '1.50',
  `urgent_fills_completed` int NOT NULL DEFAULT '0',
  `average_urgent_fill_time_hours` decimal(8,2) NOT NULL DEFAULT '0.00',
  `fill_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `shifts_declined` int NOT NULL DEFAULT '0',
  `worker_dropouts` int NOT NULL DEFAULT '0',
  `client_satisfaction_score` decimal(3,2) NOT NULL DEFAULT '0.00',
  `repeat_clients` int NOT NULL DEFAULT '0',
  `managed_workers` json DEFAULT NULL,
  `total_shifts_managed` int NOT NULL DEFAULT '0',
  `total_workers_managed` int NOT NULL DEFAULT '0',
  `active_workers` int NOT NULL DEFAULT '0',
  `available_workers` int NOT NULL DEFAULT '0',
  `average_worker_rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `worker_skill_distribution` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `business_registration_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zip_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `specializations` json DEFAULT NULL,
  `total_workers` int NOT NULL DEFAULT '0',
  `total_placements` int NOT NULL DEFAULT '0',
  `rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `is_live` tinyint(1) NOT NULL DEFAULT '0',
  `activated_at` timestamp NULL DEFAULT NULL,
  `activated_by` bigint unsigned DEFAULT NULL,
  `go_live_requested_at` timestamp NULL DEFAULT NULL,
  `compliance_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `compliance_grade` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'F',
  `compliance_last_checked` timestamp NULL DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `is_complete` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `agency_profiles_user_id_index` (`user_id`),
  KEY `agency_profiles_license_verified_index` (`license_verified`),
  KEY `agency_profiles_city_index` (`city`),
  KEY `agency_profiles_is_verified_index` (`is_verified`),
  KEY `agency_profiles_verification_status_index` (`verification_status`),
  KEY `agency_profiles_onboarding_completed_index` (`onboarding_completed`),
  KEY `agency_profiles_urgent_fill_enabled_index` (`urgent_fill_enabled`),
  KEY `agency_profiles_fill_rate_index` (`fill_rate`),
  KEY `agency_profiles_stripe_connect_account_id_index` (`stripe_connect_account_id`),
  KEY `agency_profiles_stripe_payout_enabled_index` (`stripe_payout_enabled`),
  KEY `agency_profiles_stripe_onboarding_complete_index` (`stripe_onboarding_complete`),
  KEY `agency_profiles_is_live_index` (`is_live`),
  KEY `agency_profiles_compliance_score_index` (`compliance_score`),
  KEY `agency_profiles_compliance_grade_index` (`compliance_grade`),
  KEY `agency_profiles_background_check_status_index` (`background_check_status`),
  KEY `agency_profiles_agency_tier_id_index` (`agency_tier_id`),
  CONSTRAINT `agency_profiles_agency_tier_id_foreign` FOREIGN KEY (`agency_tier_id`) REFERENCES `agency_tiers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `agency_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agency_profiles`
--

LOCK TABLES `agency_profiles` WRITE;
/*!40000 ALTER TABLE `agency_profiles` DISABLE KEYS */;
INSERT INTO `agency_profiles` VALUES (1,3,NULL,NULL,NULL,NULL,0,NULL,NULL,'Test Staffing Agency',NULL,0,NULL,NULL,NULL,0,NULL,'pending',0,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,'pending',NULL,'staffing_agency',10.00,NULL,0.00,0.00,0.00,NULL,0,NULL,0,NULL,0,0,NULL,'USD',NULL,NULL,NULL,0,0.00,0,1.50,0,0.00,0.00,0,0,0.00,0,NULL,0,0,0,0,0.00,NULL,'2025-12-19 17:58:55','2025-12-19 17:58:55',NULL,'555-0003',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0.00,0,0,NULL,NULL,NULL,0.00,'F',NULL,NULL,0);
/*!40000 ALTER TABLE `agency_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agency_tier_history`
--

DROP TABLE IF EXISTS `agency_tier_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agency_tier_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `agency_id` bigint unsigned NOT NULL,
  `from_tier_id` bigint unsigned DEFAULT NULL,
  `to_tier_id` bigint unsigned NOT NULL,
  `change_type` enum('upgrade','downgrade','initial') COLLATE utf8mb4_unicode_ci NOT NULL,
  `metrics_at_change` json NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `processed_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `agency_tier_history_from_tier_id_foreign` (`from_tier_id`),
  KEY `agency_tier_history_to_tier_id_foreign` (`to_tier_id`),
  KEY `agency_tier_history_processed_by_foreign` (`processed_by`),
  KEY `agency_tier_history_agency_id_created_at_index` (`agency_id`,`created_at`),
  KEY `agency_tier_history_change_type_index` (`change_type`),
  CONSTRAINT `agency_tier_history_agency_id_foreign` FOREIGN KEY (`agency_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `agency_tier_history_from_tier_id_foreign` FOREIGN KEY (`from_tier_id`) REFERENCES `agency_tiers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `agency_tier_history_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `agency_tier_history_to_tier_id_foreign` FOREIGN KEY (`to_tier_id`) REFERENCES `agency_tiers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agency_tier_history`
--

LOCK TABLES `agency_tier_history` WRITE;
/*!40000 ALTER TABLE `agency_tier_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `agency_tier_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agency_tiers`
--

DROP TABLE IF EXISTS `agency_tiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agency_tiers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` int NOT NULL,
  `min_monthly_revenue` decimal(12,2) NOT NULL DEFAULT '0.00',
  `min_active_workers` int NOT NULL DEFAULT '0',
  `min_fill_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `min_rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `commission_rate` decimal(5,2) NOT NULL,
  `priority_booking_hours` int NOT NULL DEFAULT '0',
  `dedicated_support` tinyint(1) NOT NULL DEFAULT '0',
  `custom_branding` tinyint(1) NOT NULL DEFAULT '0',
  `api_access` tinyint(1) NOT NULL DEFAULT '0',
  `additional_benefits` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `agency_tiers_slug_unique` (`slug`),
  KEY `agency_tiers_level_is_active_index` (`level`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agency_tiers`
--

LOCK TABLES `agency_tiers` WRITE;
/*!40000 ALTER TABLE `agency_tiers` DISABLE KEYS */;
/*!40000 ALTER TABLE `agency_tiers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agency_worker_invitations`
--

DROP TABLE IF EXISTS `agency_worker_invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agency_worker_invitations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `agency_id` bigint unsigned NOT NULL,
  `application_id` bigint unsigned DEFAULT NULL,
  `worker_email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `worker_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `worker_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invitation_token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_expires_at` timestamp NOT NULL,
  `status` enum('pending','accepted','declined','expired','cancelled','bounced') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `invited_at` timestamp NOT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `viewed_at` timestamp NULL DEFAULT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `declined_at` timestamp NULL DEFAULT NULL,
  `worker_user_id` bigint unsigned DEFAULT NULL,
  `personal_message` text COLLATE utf8mb4_unicode_ci,
  `preset_commission_rate` decimal(5,2) DEFAULT NULL,
  `preset_skills` json DEFAULT NULL,
  `preset_tags` json DEFAULT NULL,
  `reminder_count` tinyint unsigned NOT NULL DEFAULT '0',
  `last_reminder_at` timestamp NULL DEFAULT NULL,
  `invitation_source` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `import_batch_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invited_by_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accepted_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accepted_user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_active_invitation` (`agency_id`,`worker_email`,`status`),
  UNIQUE KEY `agency_worker_invitations_invitation_token_unique` (`invitation_token`),
  KEY `agency_worker_invitations_worker_user_id_foreign` (`worker_user_id`),
  KEY `agency_worker_invitations_agency_id_index` (`agency_id`),
  KEY `agency_worker_invitations_application_id_index` (`application_id`),
  KEY `agency_worker_invitations_worker_email_index` (`worker_email`),
  KEY `agency_worker_invitations_invitation_token_index` (`invitation_token`),
  KEY `agency_worker_invitations_status_index` (`status`),
  KEY `agency_worker_invitations_invited_at_index` (`invited_at`),
  KEY `agency_worker_invitations_token_expires_at_index` (`token_expires_at`),
  KEY `agency_worker_invitations_agency_id_status_index` (`agency_id`,`status`),
  KEY `agency_worker_invitations_agency_id_worker_email_index` (`agency_id`,`worker_email`),
  KEY `agency_worker_invitations_import_batch_id_index` (`import_batch_id`),
  CONSTRAINT `agency_worker_invitations_agency_id_foreign` FOREIGN KEY (`agency_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `agency_worker_invitations_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `agency_applications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `agency_worker_invitations_worker_user_id_foreign` FOREIGN KEY (`worker_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agency_worker_invitations`
--

LOCK TABLES `agency_worker_invitations` WRITE;
/*!40000 ALTER TABLE `agency_worker_invitations` DISABLE KEYS */;
/*!40000 ALTER TABLE `agency_worker_invitations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agency_workers`
--

DROP TABLE IF EXISTS `agency_workers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agency_workers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `agency_id` bigint unsigned NOT NULL,
  `worker_id` bigint unsigned NOT NULL,
  `commission_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `status` enum('active','suspended','removed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `added_at` timestamp NULL DEFAULT NULL,
  `removed_at` timestamp NULL DEFAULT NULL,
  `last_payout_transaction_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_payout_at` timestamp NULL DEFAULT NULL,
  `last_payout_amount` decimal(10,2) DEFAULT NULL,
  `total_commission_earned` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_commission_paid` decimal(12,2) NOT NULL DEFAULT '0.00',
  `pending_commission` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payout_count` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_agency_worker` (`agency_id`,`worker_id`),
  KEY `agency_workers_agency_id_index` (`agency_id`),
  KEY `agency_workers_worker_id_index` (`worker_id`),
  KEY `agency_workers_status_index` (`status`),
  KEY `agency_workers_last_payout_transaction_id_index` (`last_payout_transaction_id`),
  KEY `agency_workers_pending_commission_index` (`pending_commission`),
  CONSTRAINT `agency_workers_agency_id_foreign` FOREIGN KEY (`agency_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `agency_workers_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agency_workers`
--

LOCK TABLES `agency_workers` WRITE;
/*!40000 ALTER TABLE `agency_workers` DISABLE KEYS */;
/*!40000 ALTER TABLE `agency_workers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `alert_configurations`
--

DROP TABLE IF EXISTS `alert_configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `alert_configurations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `metric_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `warning_threshold` decimal(12,4) DEFAULT NULL,
  `critical_threshold` decimal(12,4) DEFAULT NULL,
  `comparison` enum('greater_than','less_than','equals') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'greater_than',
  `severity` enum('info','warning','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'warning',
  `slack_channel` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pagerduty_routing_key` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `slack_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `pagerduty_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `email_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `cooldown_minutes` int NOT NULL DEFAULT '60',
  `escalation_delay_minutes` int NOT NULL DEFAULT '15',
  `quiet_hours_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `quiet_hours_start` time NOT NULL DEFAULT '22:00:00',
  `quiet_hours_end` time NOT NULL DEFAULT '08:00:00',
  `additional_settings` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alert_configurations_metric_name_unique` (`metric_name`),
  KEY `alert_configurations_enabled_index` (`enabled`),
  KEY `alert_configurations_metric_name_enabled_index` (`metric_name`,`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alert_configurations`
--

LOCK TABLES `alert_configurations` WRITE;
/*!40000 ALTER TABLE `alert_configurations` DISABLE KEYS */;
/*!40000 ALTER TABLE `alert_configurations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `alert_digests`
--

DROP TABLE IF EXISTS `alert_digests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `alert_digests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `digest_key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alert_count` int NOT NULL DEFAULT '0',
  `alert_ids` json NOT NULL,
  `metrics_summary` json NOT NULL,
  `status` enum('collecting','sent','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'collecting',
  `period_start` timestamp NOT NULL,
  `period_end` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alert_digests_digest_key_unique` (`digest_key`),
  KEY `alert_digests_digest_key_index` (`digest_key`),
  KEY `alert_digests_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alert_digests`
--

LOCK TABLES `alert_digests` WRITE;
/*!40000 ALTER TABLE `alert_digests` DISABLE KEYS */;
/*!40000 ALTER TABLE `alert_digests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `alert_history`
--

DROP TABLE IF EXISTS `alert_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `alert_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `incident_id` bigint unsigned DEFAULT NULL,
  `alert_configuration_id` bigint unsigned DEFAULT NULL,
  `metric_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alert_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` enum('info','warning','critical') COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','sent','failed','suppressed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `retry_count` int NOT NULL DEFAULT '0',
  `external_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dedup_key` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acknowledged_by_user_id` bigint unsigned DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `resolved` tinyint(1) NOT NULL DEFAULT '0',
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolution_duration_minutes` int DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `alert_history_alert_configuration_id_foreign` (`alert_configuration_id`),
  KEY `alert_history_acknowledged_by_user_id_foreign` (`acknowledged_by_user_id`),
  KEY `alert_history_incident_id_index` (`incident_id`),
  KEY `alert_history_metric_name_index` (`metric_name`),
  KEY `alert_history_alert_type_index` (`alert_type`),
  KEY `alert_history_status_index` (`status`),
  KEY `alert_history_dedup_key_index` (`dedup_key`),
  KEY `alert_history_created_at_status_index` (`created_at`,`status`),
  CONSTRAINT `alert_history_acknowledged_by_user_id_foreign` FOREIGN KEY (`acknowledged_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `alert_history_alert_configuration_id_foreign` FOREIGN KEY (`alert_configuration_id`) REFERENCES `alert_configurations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `alert_history_incident_id_foreign` FOREIGN KEY (`incident_id`) REFERENCES `system_incidents` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alert_history`
--

LOCK TABLES `alert_history` WRITE;
/*!40000 ALTER TABLE `alert_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `alert_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `alert_integrations`
--

DROP TABLE IF EXISTS `alert_integrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `alert_integrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `config` json DEFAULT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT '0',
  `last_verified_at` timestamp NULL DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `total_alerts_sent` int NOT NULL DEFAULT '0',
  `failed_alerts` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alert_integrations_type_unique` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alert_integrations`
--

LOCK TABLES `alert_integrations` WRITE;
/*!40000 ALTER TABLE `alert_integrations` DISABLE KEYS */;
/*!40000 ALTER TABLE `alert_integrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_checklists`
--

DROP TABLE IF EXISTS `audit_checklists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_checklists` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `items` json NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_checklists_category_index` (`category`),
  KEY `audit_checklists_is_active_sort_order_index` (`is_active`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_checklists`
--

LOCK TABLES `audit_checklists` WRITE;
/*!40000 ALTER TABLE `audit_checklists` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_checklists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `availability_broadcasts`
--

DROP TABLE IF EXISTS `availability_broadcasts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `availability_broadcasts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `worker_id` bigint unsigned NOT NULL,
  `broadcast_type` enum('immediate','scheduled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'immediate',
  `available_from` timestamp NOT NULL,
  `available_to` timestamp NOT NULL,
  `industries` json DEFAULT NULL,
  `max_distance` int DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','expired','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `availability_broadcasts_worker_id_index` (`worker_id`),
  KEY `availability_broadcasts_status_available_from_index` (`status`,`available_from`),
  KEY `availability_broadcasts_available_from_index` (`available_from`),
  KEY `availability_broadcasts_available_to_index` (`available_to`),
  KEY `availability_broadcasts_status_index` (`status`),
  CONSTRAINT `availability_broadcasts_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `availability_broadcasts`
--

LOCK TABLES `availability_broadcasts` WRITE;
/*!40000 ALTER TABLE `availability_broadcasts` DISABLE KEYS */;
/*!40000 ALTER TABLE `availability_broadcasts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `availability_patterns`
--

DROP TABLE IF EXISTS `availability_patterns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `availability_patterns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `day_of_week` tinyint NOT NULL,
  `typical_start_time` time DEFAULT NULL,
  `typical_end_time` time DEFAULT NULL,
  `availability_probability` decimal(5,4) NOT NULL DEFAULT '0.0000',
  `historical_shifts_count` int NOT NULL DEFAULT '0',
  `historical_available_count` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `availability_patterns_user_id_day_of_week_unique` (`user_id`,`day_of_week`),
  KEY `availability_patterns_day_of_week_index` (`day_of_week`),
  CONSTRAINT `availability_patterns_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `availability_patterns`
--

LOCK TABLES `availability_patterns` WRITE;
/*!40000 ALTER TABLE `availability_patterns` DISABLE KEYS */;
/*!40000 ALTER TABLE `availability_patterns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `availability_predictions`
--

DROP TABLE IF EXISTS `availability_predictions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `availability_predictions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `prediction_date` date NOT NULL,
  `morning_probability` decimal(5,4) NOT NULL DEFAULT '0.0000',
  `afternoon_probability` decimal(5,4) NOT NULL DEFAULT '0.0000',
  `evening_probability` decimal(5,4) NOT NULL DEFAULT '0.0000',
  `night_probability` decimal(5,4) NOT NULL DEFAULT '0.0000',
  `overall_probability` decimal(5,4) NOT NULL DEFAULT '0.0000',
  `factors` json DEFAULT NULL,
  `was_accurate` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `availability_predictions_user_id_prediction_date_unique` (`user_id`,`prediction_date`),
  KEY `availability_predictions_prediction_date_index` (`prediction_date`),
  KEY `avail_pred_date_prob_idx` (`prediction_date`,`overall_probability`),
  CONSTRAINT `availability_predictions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `availability_predictions`
--

LOCK TABLES `availability_predictions` WRITE;
/*!40000 ALTER TABLE `availability_predictions` DISABLE KEYS */;
/*!40000 ALTER TABLE `availability_predictions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `background_check_consents`
--

DROP TABLE IF EXISTS `background_check_consents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `background_check_consents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `background_check_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `consent_type` enum('fcra_disclosure','fcra_authorization','dbs_consent','general_consent','data_processing') COLLATE utf8mb4_unicode_ci NOT NULL,
  `consented` tinyint(1) NOT NULL DEFAULT '0',
  `consented_at` timestamp NULL DEFAULT NULL,
  `signature_data_encrypted` text COLLATE utf8mb4_unicode_ci,
  `signature_type` enum('typed','drawn','checkbox') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signatory_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_version` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `consent_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `consent_user_agent` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `consent_device_fingerprint` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `consent_location` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_disclosure_text` text COLLATE utf8mb4_unicode_ci,
  `separate_document_provided` tinyint(1) NOT NULL DEFAULT '0',
  `is_withdrawn` tinyint(1) NOT NULL DEFAULT '0',
  `withdrawn_at` timestamp NULL DEFAULT NULL,
  `withdrawal_reason` text COLLATE utf8mb4_unicode_ci,
  `audit_log` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `background_check_consents_user_id_consent_type_index` (`user_id`,`consent_type`),
  KEY `background_check_consents_background_check_id_consented_index` (`background_check_id`,`consented`),
  CONSTRAINT `background_check_consents_background_check_id_foreign` FOREIGN KEY (`background_check_id`) REFERENCES `background_checks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `background_check_consents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `background_check_consents`
--

LOCK TABLES `background_check_consents` WRITE;
/*!40000 ALTER TABLE `background_check_consents` DISABLE KEYS */;
/*!40000 ALTER TABLE `background_check_consents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `background_checks`
--

DROP TABLE IF EXISTS `background_checks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `background_checks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `jurisdiction` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_candidate_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_report_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `check_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `check_components` json DEFAULT NULL,
  `status` enum('pending_consent','consent_received','submitted','processing','complete','consider','suspended','cancelled','expired','dispute') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending_consent',
  `result` enum('clear','consider','fail','pending') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adjudication_status` enum('not_applicable','pending','in_review','approved','denied') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_applicable',
  `adjudicated_by` bigint unsigned DEFAULT NULL,
  `adjudicated_at` timestamp NULL DEFAULT NULL,
  `adjudication_notes` text COLLATE utf8mb4_unicode_ci,
  `adverse_action_required` tinyint(1) NOT NULL DEFAULT '0',
  `pre_adverse_action_sent_at` timestamp NULL DEFAULT NULL,
  `pre_adverse_action_deadline` timestamp NULL DEFAULT NULL,
  `adverse_action_sent_at` timestamp NULL DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `cost_cents` int unsigned DEFAULT NULL,
  `cost_currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `billed_to` bigint unsigned DEFAULT NULL,
  `result_data_encrypted` text COLLATE utf8mb4_unicode_ci,
  `report_url_encrypted` text COLLATE utf8mb4_unicode_ci,
  `last_webhook_at` timestamp NULL DEFAULT NULL,
  `last_webhook_event` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `webhook_log` json DEFAULT NULL,
  `audit_log` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `background_checks_adjudicated_by_foreign` (`adjudicated_by`),
  KEY `background_checks_billed_to_foreign` (`billed_to`),
  KEY `background_checks_user_id_status_index` (`user_id`,`status`),
  KEY `background_checks_provider_provider_report_id_index` (`provider`,`provider_report_id`),
  KEY `background_checks_jurisdiction_check_type_index` (`jurisdiction`,`check_type`),
  KEY `background_checks_jurisdiction_index` (`jurisdiction`),
  KEY `background_checks_provider_report_id_index` (`provider_report_id`),
  KEY `background_checks_expires_at_index` (`expires_at`),
  CONSTRAINT `background_checks_adjudicated_by_foreign` FOREIGN KEY (`adjudicated_by`) REFERENCES `users` (`id`),
  CONSTRAINT `background_checks_billed_to_foreign` FOREIGN KEY (`billed_to`) REFERENCES `users` (`id`),
  CONSTRAINT `background_checks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `background_checks`
--

LOCK TABLES `background_checks` WRITE;
/*!40000 ALTER TABLE `background_checks` DISABLE KEYS */;
/*!40000 ALTER TABLE `background_checks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_accounts`
--

DROP TABLE IF EXISTS `bank_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `account_holder_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bank_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_code` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `currency_code` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `iban` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `routing_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bsb_code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `swift_bic` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_type` enum('checking','savings') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'checking',
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bank_accounts_user_id_is_primary_index` (`user_id`,`is_primary`),
  KEY `bank_accounts_country_code_index` (`country_code`),
  CONSTRAINT `bank_accounts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_accounts`
--

LOCK TABLES `bank_accounts` WRITE;
/*!40000 ALTER TABLE `bank_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blocked_phrases`
--

DROP TABLE IF EXISTS `blocked_phrases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blocked_phrases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `phrase` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('profanity','harassment','spam','pii','contact_info','custom') COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` enum('block','flag','redact') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'flag',
  `is_regex` tinyint(1) NOT NULL DEFAULT '0',
  `case_sensitive` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `blocked_phrases_type_index` (`type`),
  KEY `blocked_phrases_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blocked_phrases`
--

LOCK TABLES `blocked_phrases` WRITE;
/*!40000 ALTER TABLE `blocked_phrases` DISABLE KEYS */;
/*!40000 ALTER TABLE `blocked_phrases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blocked_workers`
--

DROP TABLE IF EXISTS `blocked_workers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blocked_workers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `worker_id` bigint unsigned NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `blocked_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blocked_workers_business_id_worker_id_unique` (`business_id`,`worker_id`),
  KEY `blocked_workers_worker_id_foreign` (`worker_id`),
  KEY `blocked_workers_blocked_by_foreign` (`blocked_by`),
  KEY `blocked_workers_business_id_index` (`business_id`),
  CONSTRAINT `blocked_workers_blocked_by_foreign` FOREIGN KEY (`blocked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `blocked_workers_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `blocked_workers_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blocked_workers`
--

LOCK TABLES `blocked_workers` WRITE;
/*!40000 ALTER TABLE `blocked_workers` DISABLE KEYS */;
/*!40000 ALTER TABLE `blocked_workers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `booking_confirmations`
--

DROP TABLE IF EXISTS `booking_confirmations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `booking_confirmations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shift_id` bigint unsigned NOT NULL,
  `worker_id` bigint unsigned NOT NULL,
  `business_id` bigint unsigned NOT NULL,
  `status` enum('pending','worker_confirmed','business_confirmed','fully_confirmed','declined','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `worker_confirmed` tinyint(1) NOT NULL DEFAULT '0',
  `worker_confirmed_at` timestamp NULL DEFAULT NULL,
  `business_confirmed` tinyint(1) NOT NULL DEFAULT '0',
  `business_confirmed_at` timestamp NULL DEFAULT NULL,
  `confirmation_code` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `worker_notes` text COLLATE utf8mb4_unicode_ci,
  `business_notes` text COLLATE utf8mb4_unicode_ci,
  `declined_by` bigint unsigned DEFAULT NULL,
  `declined_at` timestamp NULL DEFAULT NULL,
  `decline_reason` text COLLATE utf8mb4_unicode_ci,
  `expires_at` timestamp NOT NULL,
  `reminder_sent_at` timestamp NULL DEFAULT NULL,
  `auto_confirmed` tinyint(1) NOT NULL DEFAULT '0',
  `auto_confirm_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_confirmations_shift_id_worker_id_unique` (`shift_id`,`worker_id`),
  UNIQUE KEY `booking_confirmations_confirmation_code_unique` (`confirmation_code`),
  KEY `booking_confirmations_declined_by_foreign` (`declined_by`),
  KEY `booking_confirmations_shift_id_status_index` (`shift_id`,`status`),
  KEY `booking_confirmations_worker_id_status_index` (`worker_id`,`status`),
  KEY `booking_confirmations_business_id_status_index` (`business_id`,`status`),
  KEY `booking_confirmations_expires_at_index` (`expires_at`),
  KEY `booking_confirmations_status_index` (`status`),
  CONSTRAINT `booking_confirmations_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `booking_confirmations_declined_by_foreign` FOREIGN KEY (`declined_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `booking_confirmations_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `booking_confirmations_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `booking_confirmations`
--

LOCK TABLES `booking_confirmations` WRITE;
/*!40000 ALTER TABLE `booking_confirmations` DISABLE KEYS */;
/*!40000 ALTER TABLE `booking_confirmations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bug_reports`
--

DROP TABLE IF EXISTS `bug_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bug_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `steps_to_reproduce` text COLLATE utf8mb4_unicode_ci,
  `expected_behavior` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actual_behavior` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `severity` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `status` enum('reported','confirmed','in_progress','fixed','closed','wont_fix') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reported',
  `attachments` json DEFAULT NULL,
  `browser` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `os` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `app_version` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bug_reports_status_severity_index` (`status`,`severity`),
  KEY `bug_reports_user_id_index` (`user_id`),
  KEY `bug_reports_severity_status_index` (`severity`,`status`),
  KEY `bug_reports_created_at_index` (`created_at`),
  CONSTRAINT `bug_reports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bug_reports`
--

LOCK TABLES `bug_reports` WRITE;
/*!40000 ALTER TABLE `bug_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `bug_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_addresses`
--

DROP TABLE IF EXISTS `business_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `business_addresses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_profile_id` bigint unsigned NOT NULL,
  `address_type` enum('registered','billing','operating','mailing','headquarters') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'operating',
  `label` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_1` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line_2` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `state_province` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `postal_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `country_code` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `country_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `timezone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jurisdiction_code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_region` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `business_addresses_business_profile_id_index` (`business_profile_id`),
  KEY `business_addresses_address_type_index` (`address_type`),
  KEY `business_addresses_latitude_longitude_index` (`latitude`,`longitude`),
  KEY `business_addresses_country_code_index` (`country_code`),
  KEY `business_addresses_is_primary_index` (`is_primary`),
  CONSTRAINT `business_addresses_business_profile_id_foreign` FOREIGN KEY (`business_profile_id`) REFERENCES `business_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_addresses`
--

LOCK TABLES `business_addresses` WRITE;
/*!40000 ALTER TABLE `business_addresses` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_cancellation_logs`
--

DROP TABLE IF EXISTS `business_cancellation_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `business_cancellation_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_profile_id` bigint unsigned NOT NULL,
  `shift_id` bigint unsigned NOT NULL,
  `cancelled_by_user_id` bigint unsigned NOT NULL,
  `cancellation_type` enum('on_time','late','no_show','emergency') COLLATE utf8mb4_unicode_ci NOT NULL,
  `cancellation_reason` text COLLATE utf8mb4_unicode_ci,
  `hours_before_shift` int DEFAULT NULL,
  `shift_start_time` timestamp NOT NULL,
  `shift_end_time` timestamp NOT NULL,
  `shift_pay_rate` int NOT NULL,
  `shift_role` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancellation_fee` int NOT NULL DEFAULT '0',
  `fee_waived` tinyint(1) NOT NULL DEFAULT '0',
  `fee_waiver_reason` text COLLATE utf8mb4_unicode_ci,
  `total_cancellations_at_time` int NOT NULL DEFAULT '0',
  `cancellations_last_30_days_at_time` int NOT NULL DEFAULT '0',
  `cancellation_rate_at_time` decimal(5,2) NOT NULL DEFAULT '0.00',
  `warning_issued` tinyint(1) NOT NULL DEFAULT '0',
  `escrow_increased` tinyint(1) NOT NULL DEFAULT '0',
  `credit_suspended` tinyint(1) NOT NULL DEFAULT '0',
  `action_taken_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `business_cancellation_logs_cancelled_by_user_id_foreign` (`cancelled_by_user_id`),
  KEY `business_cancellation_logs_business_profile_id_index` (`business_profile_id`),
  KEY `business_cancellation_logs_shift_id_index` (`shift_id`),
  KEY `business_cancellation_logs_cancellation_type_index` (`cancellation_type`),
  KEY `business_cancellation_logs_created_at_index` (`created_at`),
  CONSTRAINT `business_cancellation_logs_business_profile_id_foreign` FOREIGN KEY (`business_profile_id`) REFERENCES `business_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_cancellation_logs_cancelled_by_user_id_foreign` FOREIGN KEY (`cancelled_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_cancellation_logs_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_cancellation_logs`
--

LOCK TABLES `business_cancellation_logs` WRITE;
/*!40000 ALTER TABLE `business_cancellation_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_cancellation_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_contacts`
--

DROP TABLE IF EXISTS `business_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `business_contacts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_profile_id` bigint unsigned NOT NULL,
  `contact_type` enum('primary','billing','operations','emergency','hr') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'primary',
  `first_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `job_title` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_extension` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receives_shift_notifications` tinyint(1) NOT NULL DEFAULT '1',
  `receives_billing_notifications` tinyint(1) NOT NULL DEFAULT '0',
  `receives_marketing_emails` tinyint(1) NOT NULL DEFAULT '0',
  `preferred_contact_method` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email',
  `email_verified` tinyint(1) NOT NULL DEFAULT '0',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `verification_token` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `business_contacts_business_profile_id_index` (`business_profile_id`),
  KEY `business_contacts_contact_type_index` (`contact_type`),
  KEY `business_contacts_email_index` (`email`),
  KEY `business_contacts_is_primary_index` (`is_primary`),
  CONSTRAINT `business_contacts_business_profile_id_foreign` FOREIGN KEY (`business_profile_id`) REFERENCES `business_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_contacts`
--

LOCK TABLES `business_contacts` WRITE;
/*!40000 ALTER TABLE `business_contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_credit_transactions`
--

DROP TABLE IF EXISTS `business_credit_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `business_credit_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `shift_id` bigint unsigned DEFAULT NULL,
  `invoice_id` bigint unsigned DEFAULT NULL,
  `transaction_type` enum('charge','payment','late_fee','refund','adjustment','credit_increase','credit_decrease') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `balance_before` decimal(12,2) NOT NULL,
  `balance_after` decimal(12,2) NOT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `reference_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `business_credit_transactions_shift_id_foreign` (`shift_id`),
  KEY `business_credit_transactions_business_id_index` (`business_id`),
  KEY `business_credit_transactions_business_id_transaction_type_index` (`business_id`,`transaction_type`),
  KEY `business_credit_transactions_business_id_created_at_index` (`business_id`,`created_at`),
  KEY `business_credit_transactions_transaction_type_index` (`transaction_type`),
  KEY `business_credit_transactions_reference_id_index` (`reference_id`),
  CONSTRAINT `business_credit_transactions_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_credit_transactions_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_credit_transactions`
--

LOCK TABLES `business_credit_transactions` WRITE;
/*!40000 ALTER TABLE `business_credit_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_credit_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_document_access_logs`
--

DROP TABLE IF EXISTS `business_document_access_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `business_document_access_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_document_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `bdal_doc_created_idx` (`business_document_id`,`created_at`),
  KEY `bdal_user_action_idx` (`user_id`,`action`),
  CONSTRAINT `business_document_access_logs_business_document_id_foreign` FOREIGN KEY (`business_document_id`) REFERENCES `business_documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_document_access_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_document_access_logs`
--

LOCK TABLES `business_document_access_logs` WRITE;
/*!40000 ALTER TABLE `business_document_access_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_document_access_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_documents`
--

DROP TABLE IF EXISTS `business_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `business_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_verification_id` bigint unsigned NOT NULL,
  `business_profile_id` bigint unsigned NOT NULL,
  `requirement_id` bigint unsigned DEFAULT NULL,
  `document_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path_encrypted` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_filename` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint unsigned DEFAULT NULL,
  `storage_provider` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 's3',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `extracted_data` json DEFAULT NULL,
  `ocr_confidence` float DEFAULT NULL,
  `extracted_at` timestamp NULL DEFAULT NULL,
  `data_validated` tinyint(1) NOT NULL DEFAULT '0',
  `validation_results` json DEFAULT NULL,
  `validated_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_notes` text COLLATE utf8mb4_unicode_ci,
  `rejection_reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `expiry_notified` tinyint(1) NOT NULL DEFAULT '0',
  `access_token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `access_token_expires_at` timestamp NULL DEFAULT NULL,
  `download_count` int NOT NULL DEFAULT '0',
  `last_accessed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `business_documents_access_token_unique` (`access_token`),
  KEY `business_documents_requirement_id_foreign` (`requirement_id`),
  KEY `business_documents_reviewed_by_foreign` (`reviewed_by`),
  KEY `bd_verification_type_idx` (`business_verification_id`,`document_type`),
  KEY `bd_bp_status_idx` (`business_profile_id`,`status`),
  KEY `bd_status_expiry_idx` (`status`,`expiry_date`),
  KEY `bd_access_token_idx` (`access_token`),
  CONSTRAINT `business_documents_business_profile_id_foreign` FOREIGN KEY (`business_profile_id`) REFERENCES `business_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_documents_business_verification_id_foreign` FOREIGN KEY (`business_verification_id`) REFERENCES `business_verifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_documents_requirement_id_foreign` FOREIGN KEY (`requirement_id`) REFERENCES `verification_requirements` (`id`),
  CONSTRAINT `business_documents_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_documents`
--

LOCK TABLES `business_documents` WRITE;
/*!40000 ALTER TABLE `business_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_onboarding`
--

DROP TABLE IF EXISTS `business_onboarding`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `business_onboarding` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_profile_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `current_step` int NOT NULL DEFAULT '1',
  `total_steps` int NOT NULL DEFAULT '6',
  `completion_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `status` enum('not_started','in_progress','pending_review','completed','suspended') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_started',
  `steps_completed` json DEFAULT NULL,
  `profile_completion_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `missing_fields` json DEFAULT NULL,
  `optional_fields_completed` json DEFAULT NULL,
  `signup_source` enum('organic','referral','sales_assisted','partnership','advertising') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'organic',
  `referral_code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referred_by_business_id` bigint unsigned DEFAULT NULL,
  `sales_rep_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `utm_source` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `utm_medium` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `utm_campaign` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_domain` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT '0',
  `profile_minimum_met` tinyint(1) NOT NULL DEFAULT '0',
  `terms_accepted` tinyint(1) NOT NULL DEFAULT '0',
  `terms_accepted_at` timestamp NULL DEFAULT NULL,
  `terms_version` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_method_added` tinyint(1) NOT NULL DEFAULT '0',
  `is_activated` tinyint(1) NOT NULL DEFAULT '0',
  `activated_at` timestamp NULL DEFAULT NULL,
  `last_reminder_sent_at` timestamp NULL DEFAULT NULL,
  `reminders_sent_count` int NOT NULL DEFAULT '0',
  `next_reminder_at` timestamp NULL DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `time_to_complete_minutes` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `business_onboarding_referred_by_business_id_foreign` (`referred_by_business_id`),
  KEY `business_onboarding_business_profile_id_index` (`business_profile_id`),
  KEY `business_onboarding_user_id_index` (`user_id`),
  KEY `business_onboarding_status_index` (`status`),
  KEY `business_onboarding_email_domain_index` (`email_domain`),
  KEY `business_onboarding_signup_source_index` (`signup_source`),
  KEY `business_onboarding_referral_code_index` (`referral_code`),
  KEY `business_onboarding_is_activated_index` (`is_activated`),
  CONSTRAINT `business_onboarding_business_profile_id_foreign` FOREIGN KEY (`business_profile_id`) REFERENCES `business_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_onboarding_referred_by_business_id_foreign` FOREIGN KEY (`referred_by_business_id`) REFERENCES `business_profiles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `business_onboarding_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_onboarding`
--

LOCK TABLES `business_onboarding` WRITE;
/*!40000 ALTER TABLE `business_onboarding` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_onboarding` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_payment_methods`
--

DROP TABLE IF EXISTS `business_payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `business_payment_methods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_profile_id` bigint unsigned NOT NULL,
  `stripe_customer_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_payment_method_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_setup_intent_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'card',
  `display_brand` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_last4` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_exp_month` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_exp_year` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_routing_display` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `iban_last4` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_code_display` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verification_status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `verification_method` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verification_requested_at` timestamp NULL DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `verification_failure_reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `micro_deposit_attempts` int NOT NULL DEFAULT '0',
  `micro_deposit_sent_at` timestamp NULL DEFAULT NULL,
  `three_d_secure_supported` tinyint(1) NOT NULL DEFAULT '0',
  `three_d_secure_status` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_address_line1` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_address_line2` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_city` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_state` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_postal_code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_country` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `auto_retry_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `max_retry_attempts` int NOT NULL DEFAULT '3',
  `failed_payment_count` int NOT NULL DEFAULT '0',
  `last_failed_at` timestamp NULL DEFAULT NULL,
  `last_failure_reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nickname` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `business_payment_methods_business_profile_id_is_default_index` (`business_profile_id`,`is_default`),
  KEY `business_payment_methods_business_profile_id_is_active_index` (`business_profile_id`,`is_active`),
  KEY `business_payment_methods_type_verification_status_index` (`type`,`verification_status`),
  KEY `business_payment_methods_stripe_customer_id_index` (`stripe_customer_id`),
  KEY `business_payment_methods_stripe_payment_method_id_index` (`stripe_payment_method_id`),
  CONSTRAINT `business_payment_methods_business_profile_id_foreign` FOREIGN KEY (`business_profile_id`) REFERENCES `business_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_payment_methods`
--

LOCK TABLES `business_payment_methods` WRITE;
/*!40000 ALTER TABLE `business_payment_methods` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_payment_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_profiles`
--

DROP TABLE IF EXISTS `business_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `business_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `stripe_customer_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_setup_complete` tinyint(1) NOT NULL DEFAULT '0',
  `payment_setup_at` timestamp NULL DEFAULT NULL,
  `default_payment_method` bigint unsigned DEFAULT NULL,
  `primary_admin_user_id` bigint unsigned DEFAULT NULL,
  `onboarding_completed` tinyint(1) NOT NULL DEFAULT '0',
  `onboarding_step` int DEFAULT NULL,
  `onboarding_completed_at` timestamp NULL DEFAULT NULL,
  `business_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `legal_business_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trading_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `business_type` enum('independent','small_business','enterprise') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'small_business',
  `business_category` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `industry` enum('hospitality','healthcare','retail','events','warehouse','professional') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `business_address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `business_city` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `business_state` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `business_country` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `business_phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ein_tax_id` text COLLATE utf8mb4_unicode_ci,
  `rating_average` decimal(3,2) NOT NULL DEFAULT '0.00',
  `avg_punctuality` decimal(3,2) DEFAULT NULL,
  `avg_communication` decimal(3,2) DEFAULT NULL,
  `avg_professionalism` decimal(3,2) DEFAULT NULL,
  `avg_payment_reliability` decimal(3,2) DEFAULT NULL,
  `weighted_rating` decimal(3,2) DEFAULT NULL,
  `total_ratings_count` int unsigned NOT NULL DEFAULT '0',
  `total_reviews` int NOT NULL DEFAULT '0',
  `communication_rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `punctuality_rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `professionalism_rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `total_shifts_posted` int NOT NULL DEFAULT '0',
  `total_shifts_completed` int NOT NULL DEFAULT '0',
  `total_shifts_cancelled` int NOT NULL DEFAULT '0',
  `average_shift_cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_spent` decimal(12,2) NOT NULL DEFAULT '0.00',
  `pending_payment` decimal(12,2) NOT NULL DEFAULT '0.00',
  `unique_workers_hired` int NOT NULL DEFAULT '0',
  `repeat_workers` int NOT NULL DEFAULT '0',
  `subscription_plan` enum('free','basic','professional','enterprise') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'free',
  `current_volume_tier_id` bigint unsigned DEFAULT NULL,
  `lifetime_shifts` int NOT NULL DEFAULT '0',
  `lifetime_spend` decimal(15,2) NOT NULL DEFAULT '0.00',
  `lifetime_savings` decimal(12,2) NOT NULL DEFAULT '0.00',
  `custom_pricing` tinyint(1) NOT NULL DEFAULT '0',
  `custom_fee_percent` decimal(5,2) DEFAULT NULL,
  `custom_pricing_notes` text COLLATE utf8mb4_unicode_ci,
  `custom_pricing_expires_at` date DEFAULT NULL,
  `tier_upgraded_at` timestamp NULL DEFAULT NULL,
  `tier_downgraded_at` timestamp NULL DEFAULT NULL,
  `months_at_current_tier` int NOT NULL DEFAULT '0',
  `subscription_expires_at` timestamp NULL DEFAULT NULL,
  `monthly_credit_limit` decimal(10,2) DEFAULT NULL,
  `monthly_credit_used` decimal(10,2) NOT NULL DEFAULT '0.00',
  `fill_rate` decimal(3,2) NOT NULL DEFAULT '0.00',
  `monthly_budget` int NOT NULL DEFAULT '0',
  `cancellation_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `late_cancellations` int NOT NULL DEFAULT '0',
  `total_cancellation_penalties` decimal(10,2) NOT NULL DEFAULT '0.00',
  `open_support_tickets` int NOT NULL DEFAULT '0',
  `last_support_contact` timestamp NULL DEFAULT NULL,
  `priority_support` tinyint(1) NOT NULL DEFAULT '0',
  `account_in_good_standing` tinyint(1) NOT NULL DEFAULT '1',
  `account_warning_message` text COLLATE utf8mb4_unicode_ci,
  `last_shift_posted_at` timestamp NULL DEFAULT NULL,
  `can_post_shifts` tinyint(1) NOT NULL DEFAULT '1',
  `activation_checked` tinyint(1) NOT NULL DEFAULT '0',
  `activation_checked_at` timestamp NULL DEFAULT NULL,
  `last_activation_check` timestamp NULL DEFAULT NULL,
  `activation_requirements_status` json DEFAULT NULL,
  `activation_completion_percentage` int NOT NULL DEFAULT '0',
  `activation_requirements_met` int NOT NULL DEFAULT '0',
  `activation_requirements_total` int NOT NULL DEFAULT '6',
  `activation_blocked_reasons` json DEFAULT NULL,
  `activation_notes` text COLLATE utf8mb4_unicode_ci,
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `credit_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `credit_limit` decimal(12,2) NOT NULL DEFAULT '0.00',
  `credit_used` decimal(12,2) NOT NULL DEFAULT '0.00',
  `credit_available` decimal(12,2) NOT NULL DEFAULT '0.00',
  `credit_utilization` decimal(5,2) NOT NULL DEFAULT '0.00',
  `payment_terms` enum('net_7','net_14','net_30') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'net_14',
  `interest_rate_monthly` decimal(5,2) NOT NULL DEFAULT '1.50',
  `credit_paused` tinyint(1) NOT NULL DEFAULT '0',
  `credit_paused_at` timestamp NULL DEFAULT NULL,
  `credit_pause_reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `late_payment_count` int NOT NULL DEFAULT '0',
  `last_late_payment_at` timestamp NULL DEFAULT NULL,
  `total_late_fees` decimal(10,2) NOT NULL DEFAULT '0.00',
  `credit_approved_at` timestamp NULL DEFAULT NULL,
  `verification_status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `verification_notes` text COLLATE utf8mb4_unicode_ci,
  `business_license_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `insurance_certificate_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_document_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `documents_submitted_at` timestamp NULL DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `business_registration_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `work_email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `work_email_domain` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `work_email_verified` tinyint(1) NOT NULL DEFAULT '0',
  `work_email_verified_at` timestamp NULL DEFAULT NULL,
  `email_verification_token` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verification_sent_at` timestamp NULL DEFAULT NULL,
  `registration_source` enum('self_service','sales_assisted','referral','partnership','import') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'self_service',
  `sales_rep_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sales_rep_email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referral_code_used` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_public_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zip_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `default_timezone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jurisdiction_country` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jurisdiction_state` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_jurisdiction` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `total_locations` int NOT NULL DEFAULT '1',
  `multi_location_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `active_venues` int NOT NULL DEFAULT '0',
  `total_templates` int NOT NULL DEFAULT '0',
  `active_templates` int NOT NULL DEFAULT '0',
  `employee_count` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_size` enum('sole_proprietor','micro','small','medium','large','enterprise') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `has_payment_method` tinyint(1) NOT NULL DEFAULT '0',
  `autopay_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `default_payment_method_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferred_worker_ids` json DEFAULT NULL,
  `blacklisted_worker_ids` json DEFAULT NULL,
  `allow_new_workers` tinyint(1) NOT NULL DEFAULT '1',
  `minimum_worker_rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `minimum_shifts_completed` int NOT NULL DEFAULT '0',
  `is_complete` tinyint(1) NOT NULL DEFAULT '0',
  `profile_completion_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `profile_completion_details` json DEFAULT NULL,
  `current_month_spend` int NOT NULL DEFAULT '0',
  `ytd_spend` int NOT NULL DEFAULT '0',
  `enable_budget_alerts` tinyint(1) NOT NULL DEFAULT '1',
  `budget_alert_threshold_75` int NOT NULL DEFAULT '75',
  `budget_alert_threshold_90` int NOT NULL DEFAULT '90',
  `budget_alert_threshold_100` int NOT NULL DEFAULT '100',
  `last_budget_alert_sent_at` timestamp NULL DEFAULT NULL,
  `total_late_cancellations` int NOT NULL DEFAULT '0',
  `late_cancellations_last_30_days` int NOT NULL DEFAULT '0',
  `last_late_cancellation_at` timestamp NULL DEFAULT NULL,
  `requires_increased_escrow` tinyint(1) NOT NULL DEFAULT '0',
  `credit_suspended` tinyint(1) NOT NULL DEFAULT '0',
  `credit_suspended_at` timestamp NULL DEFAULT NULL,
  `credit_suspension_reason` text COLLATE utf8mb4_unicode_ci,
  `credit_approved_by` bigint unsigned DEFAULT NULL,
  `last_credit_review_at` timestamp NULL DEFAULT NULL,
  `payment_retry_max_attempts` int NOT NULL DEFAULT '3',
  `payment_retry_interval_days` int NOT NULL DEFAULT '3',
  `payment_auto_retry` tinyint(1) NOT NULL DEFAULT '1',
  `billing_email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `send_payment_receipts` tinyint(1) NOT NULL DEFAULT '1',
  `invoice_auto_pay` tinyint(1) NOT NULL DEFAULT '1',
  `first_shift_posted` tinyint(1) NOT NULL DEFAULT '0',
  `first_shift_posted_at` timestamp NULL DEFAULT NULL,
  `promotional_credits_cents` int NOT NULL DEFAULT '0',
  `credits_expire_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `business_profiles_user_id_index` (`user_id`),
  KEY `business_profiles_business_type_index` (`business_type`),
  KEY `business_profiles_industry_index` (`industry`),
  KEY `business_profiles_rating_average_index` (`rating_average`),
  KEY `business_profiles_city_index` (`city`),
  KEY `business_profiles_subscription_plan_index` (`subscription_plan`),
  KEY `business_profiles_verification_status_index` (`verification_status`),
  KEY `business_profiles_onboarding_completed_index` (`onboarding_completed`),
  KEY `business_profiles_account_in_good_standing_index` (`account_in_good_standing`),
  KEY `business_profiles_last_shift_posted_at_index` (`last_shift_posted_at`),
  KEY `business_profiles_credit_approved_by_foreign` (`credit_approved_by`),
  KEY `business_profiles_credit_enabled_index` (`credit_enabled`),
  KEY `business_profiles_credit_enabled_credit_paused_index` (`credit_enabled`,`credit_paused`),
  KEY `business_profiles_credit_utilization_index` (`credit_utilization`),
  KEY `business_profiles_work_email_domain_index` (`work_email_domain`),
  KEY `business_profiles_business_category_index` (`business_category`),
  KEY `business_profiles_company_size_index` (`company_size`),
  KEY `business_profiles_registration_source_index` (`registration_source`),
  KEY `business_profiles_stripe_customer_id_index` (`stripe_customer_id`),
  KEY `business_profiles_activation_checked_index` (`activation_checked`),
  KEY `business_profiles_activation_checked_at_index` (`activation_checked_at`),
  KEY `business_profiles_weighted_rating_index` (`weighted_rating`),
  KEY `business_profiles_avg_communication_index` (`avg_communication`),
  KEY `business_profiles_current_volume_tier_id_index` (`current_volume_tier_id`),
  KEY `business_profiles_custom_pricing_index` (`custom_pricing`),
  CONSTRAINT `business_profiles_credit_approved_by_foreign` FOREIGN KEY (`credit_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `business_profiles_current_volume_tier_id_foreign` FOREIGN KEY (`current_volume_tier_id`) REFERENCES `volume_discount_tiers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `business_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_profiles`
--

LOCK TABLES `business_profiles` WRITE;
/*!40000 ALTER TABLE `business_profiles` DISABLE KEYS */;
INSERT INTO `business_profiles` VALUES (1,2,NULL,0,NULL,NULL,NULL,1,NULL,NULL,'Test Company LLC',NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,NULL,NULL,NULL,0,0,0.00,0.00,0.00,0,0,0,0.00,0.00,0.00,0,0,'free',NULL,0,0.00,0.00,0,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,0.00,0.00,0,0.00,0,0.00,0,NULL,0,1,NULL,NULL,1,0,NULL,NULL,NULL,0,0,6,NULL,NULL,1,0,0.00,0.00,0.00,0.00,'net_14',1.50,0,NULL,NULL,0,NULL,0.00,NULL,'pending',NULL,NULL,NULL,NULL,NULL,NULL,'2025-12-19 17:58:55','2025-12-19 17:58:55',NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,'self_service',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'USD',NULL,NULL,NULL,NULL,NULL,1,0,0,0,0,NULL,NULL,0.00,0,0,NULL,NULL,NULL,1,0.00,0,1,0.00,NULL,0,0,1,75,90,100,NULL,0,0,NULL,0,0,NULL,NULL,NULL,NULL,3,3,1,NULL,1,1,0,NULL,0,NULL);
/*!40000 ALTER TABLE `business_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_referrals`
--

DROP TABLE IF EXISTS `business_referrals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `business_referrals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `referrer_business_id` bigint unsigned NOT NULL,
  `referrer_user_id` bigint unsigned NOT NULL,
  `referred_business_id` bigint unsigned DEFAULT NULL,
  `referred_user_id` bigint unsigned DEFAULT NULL,
  `referral_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `referred_email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `referred_company_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','clicked','registered','activated','first_shift','qualified','rewarded','expired','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reward_eligible` tinyint(1) NOT NULL DEFAULT '0',
  `referrer_reward_amount` decimal(10,2) DEFAULT NULL,
  `referred_reward_amount` decimal(10,2) DEFAULT NULL,
  `reward_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reward_issued_at` timestamp NULL DEFAULT NULL,
  `reward_transaction_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `required_shifts_posted` int NOT NULL DEFAULT '1',
  `actual_shifts_posted` int NOT NULL DEFAULT '0',
  `required_spend_amount` decimal(10,2) DEFAULT NULL,
  `actual_spend_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `qualification_days` int NOT NULL DEFAULT '30',
  `qualification_deadline` timestamp NULL DEFAULT NULL,
  `invitation_sent_at` timestamp NULL DEFAULT NULL,
  `link_clicked_at` timestamp NULL DEFAULT NULL,
  `registered_at` timestamp NULL DEFAULT NULL,
  `activated_at` timestamp NULL DEFAULT NULL,
  `first_shift_at` timestamp NULL DEFAULT NULL,
  `qualified_at` timestamp NULL DEFAULT NULL,
  `reminder_count` int NOT NULL DEFAULT '0',
  `last_reminder_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `business_referrals_referral_code_unique` (`referral_code`),
  KEY `business_referrals_referrer_user_id_foreign` (`referrer_user_id`),
  KEY `business_referrals_referred_user_id_foreign` (`referred_user_id`),
  KEY `business_referrals_referrer_business_id_index` (`referrer_business_id`),
  KEY `business_referrals_referred_business_id_index` (`referred_business_id`),
  KEY `business_referrals_referral_code_index` (`referral_code`),
  KEY `business_referrals_referred_email_index` (`referred_email`),
  KEY `business_referrals_status_index` (`status`),
  KEY `business_referrals_reward_eligible_index` (`reward_eligible`),
  CONSTRAINT `business_referrals_referred_business_id_foreign` FOREIGN KEY (`referred_business_id`) REFERENCES `business_profiles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `business_referrals_referred_user_id_foreign` FOREIGN KEY (`referred_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `business_referrals_referrer_business_id_foreign` FOREIGN KEY (`referrer_business_id`) REFERENCES `business_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_referrals_referrer_user_id_foreign` FOREIGN KEY (`referrer_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_referrals`
--

LOCK TABLES `business_referrals` WRITE;
/*!40000 ALTER TABLE `business_referrals` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_referrals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_rosters`
--

DROP TABLE IF EXISTS `business_rosters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `business_rosters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` enum('preferred','regular','backup','blacklist') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'regular',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `business_rosters_business_id_type_index` (`business_id`,`type`),
  KEY `business_rosters_business_id_is_default_index` (`business_id`,`is_default`),
  CONSTRAINT `business_rosters_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_rosters`
--

LOCK TABLES `business_rosters` WRITE;
/*!40000 ALTER TABLE `business_rosters` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_rosters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_types`
--

DROP TABLE IF EXISTS `business_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `business_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `enabled_features` json DEFAULT NULL,
  `industry_settings` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `business_types_code_unique` (`code`),
  KEY `business_types_category_index` (`category`),
  KEY `business_types_is_active_index` (`is_active`),
  KEY `business_types_sort_order_index` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_types`
--

LOCK TABLES `business_types` WRITE;
/*!40000 ALTER TABLE `business_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_verifications`
--

DROP TABLE IF EXISTS `business_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `business_verifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_profile_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `jurisdiction` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `verification_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'kyb',
  `legal_business_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trading_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registration_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `business_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `incorporation_state` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `incorporation_country` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `incorporation_date` date DEFAULT NULL,
  `registered_address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registered_city` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registered_state` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registered_postal_code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registered_country` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `review_started_at` timestamp NULL DEFAULT NULL,
  `reviewer_id` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_notes` text COLLATE utf8mb4_unicode_ci,
  `rejection_reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rejection_details` json DEFAULT NULL,
  `auto_verification_results` json DEFAULT NULL,
  `auto_verified` tinyint(1) NOT NULL DEFAULT '0',
  `auto_verified_at` timestamp NULL DEFAULT NULL,
  `requires_manual_review` tinyint(1) NOT NULL DEFAULT '0',
  `manual_review_reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `review_priority` int NOT NULL DEFAULT '0',
  `valid_until` date DEFAULT NULL,
  `expiry_notified` tinyint(1) NOT NULL DEFAULT '0',
  `submission_attempts` int NOT NULL DEFAULT '0',
  `last_attempt_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `business_verifications_user_id_foreign` (`user_id`),
  KEY `bv_bp_status_idx` (`business_profile_id`,`status`),
  KEY `bv_status_review_idx` (`status`,`requires_manual_review`),
  KEY `bv_jurisdiction_status_idx` (`jurisdiction`,`status`),
  KEY `bv_reviewer_status_idx` (`reviewer_id`,`status`),
  KEY `bv_valid_until_idx` (`valid_until`),
  CONSTRAINT `business_verifications_business_profile_id_foreign` FOREIGN KEY (`business_profile_id`) REFERENCES `business_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_verifications_reviewer_id_foreign` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`),
  CONSTRAINT `business_verifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_verifications`
--

LOCK TABLES `business_verifications` WRITE;
/*!40000 ALTER TABLE `business_verifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_verifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_volume_tracking`
--

DROP TABLE IF EXISTS `business_volume_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `business_volume_tracking` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `month` date NOT NULL,
  `shifts_posted` int NOT NULL DEFAULT '0',
  `shifts_filled` int NOT NULL DEFAULT '0',
  `shifts_completed` int NOT NULL DEFAULT '0',
  `shifts_cancelled` int NOT NULL DEFAULT '0',
  `total_spend` decimal(12,2) NOT NULL DEFAULT '0.00',
  `platform_fees_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `platform_fees_without_discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `applied_tier_id` bigint unsigned DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `average_shift_value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `unique_workers_hired` int NOT NULL DEFAULT '0',
  `repeat_workers` int NOT NULL DEFAULT '0',
  `daily_breakdown` json DEFAULT NULL,
  `tier_qualified_at` timestamp NULL DEFAULT NULL,
  `tier_notified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `business_volume_tracking_business_id_month_unique` (`business_id`,`month`),
  KEY `business_volume_tracking_month_index` (`month`),
  KEY `business_volume_tracking_applied_tier_id_index` (`applied_tier_id`),
  KEY `business_volume_tracking_business_id_month_index` (`business_id`,`month`),
  CONSTRAINT `business_volume_tracking_applied_tier_id_foreign` FOREIGN KEY (`applied_tier_id`) REFERENCES `volume_discount_tiers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `business_volume_tracking_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_volume_tracking`
--

LOCK TABLES `business_volume_tracking` WRITE;
/*!40000 ALTER TABLE `business_volume_tracking` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_volume_tracking` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_worker_roster`
--

DROP TABLE IF EXISTS `business_worker_roster`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `business_worker_roster` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `worker_id` bigint unsigned NOT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'neutral',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `business_worker_roster_business_id_worker_id_unique` (`business_id`,`worker_id`),
  KEY `business_worker_roster_worker_id_foreign` (`worker_id`),
  KEY `business_worker_roster_status_index` (`status`),
  CONSTRAINT `business_worker_roster_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_worker_roster_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_worker_roster`
--

LOCK TABLES `business_worker_roster` WRITE;
/*!40000 ALTER TABLE `business_worker_roster` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_worker_roster` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `certification_documents`
--

DROP TABLE IF EXISTS `certification_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `certification_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `worker_certification_id` bigint unsigned NOT NULL,
  `worker_id` bigint unsigned NOT NULL,
  `document_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_filename` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stored_filename` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int NOT NULL,
  `file_hash` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `storage_disk` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 's3',
  `storage_path` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `storage_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_encrypted` tinyint(1) NOT NULL DEFAULT '1',
  `encryption_algorithm` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'AES-256-GCM',
  `encryption_key_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `encryption_iv` text COLLATE utf8mb4_unicode_ci,
  `ocr_processed` tinyint(1) NOT NULL DEFAULT '0',
  `ocr_processed_at` timestamp NULL DEFAULT NULL,
  `ocr_results` json DEFAULT NULL,
  `ocr_confidence` decimal(5,2) DEFAULT NULL,
  `status` enum('pending','active','archived','deleted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `is_current` tinyint(1) NOT NULL DEFAULT '1',
  `exif_data` json DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `document_date` timestamp NULL DEFAULT NULL,
  `uploaded_by` bigint unsigned DEFAULT NULL,
  `uploaded_from_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_user_agent` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `certification_documents_uploaded_by_foreign` (`uploaded_by`),
  KEY `certification_documents_worker_certification_id_index` (`worker_certification_id`),
  KEY `certification_documents_worker_id_index` (`worker_id`),
  KEY `certification_documents_document_type_index` (`document_type`),
  KEY `certification_documents_status_index` (`status`),
  KEY `certification_documents_is_current_index` (`is_current`),
  KEY `certification_documents_ocr_processed_index` (`ocr_processed`),
  KEY `certification_documents_worker_id_document_type_index` (`worker_id`,`document_type`),
  CONSTRAINT `certification_documents_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `certification_documents_worker_certification_id_foreign` FOREIGN KEY (`worker_certification_id`) REFERENCES `worker_certifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `certification_documents_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `certification_documents`
--

LOCK TABLES `certification_documents` WRITE;
/*!40000 ALTER TABLE `certification_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `certification_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `certification_types`
--

DROP TABLE IF EXISTS `certification_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `certification_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `short_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `industry` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issuing_organization` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issuing_organization_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recognized_issuers` json DEFAULT NULL,
  `has_expiration` tinyint(1) NOT NULL DEFAULT '1',
  `default_validity_months` int DEFAULT NULL,
  `renewal_reminder_days` int NOT NULL DEFAULT '60',
  `auto_verifiable` tinyint(1) NOT NULL DEFAULT '0',
  `verification_api_provider` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verification_config` json DEFAULT NULL,
  `requires_document_upload` tinyint(1) NOT NULL DEFAULT '1',
  `required_document_types` json DEFAULT NULL,
  `renewal_instructions` text COLLATE utf8mb4_unicode_ci,
  `available_countries` json DEFAULT NULL,
  `available_states` json DEFAULT NULL,
  `icon` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `certification_types_slug_unique` (`slug`),
  KEY `ct_industry_idx` (`industry`),
  KEY `ct_category_idx` (`category`),
  KEY `ct_is_active_idx` (`is_active`),
  KEY `ct_auto_verifiable_idx` (`auto_verifiable`),
  KEY `ct_industry_category_idx` (`industry`,`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `certification_types`
--

LOCK TABLES `certification_types` WRITE;
/*!40000 ALTER TABLE `certification_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `certification_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `certifications`
--

DROP TABLE IF EXISTS `certifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `certifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `industry` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issuing_organization` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `certifications_name_index` (`name`),
  KEY `certifications_industry_index` (`industry`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `certifications`
--

LOCK TABLES `certifications` WRITE;
/*!40000 ALTER TABLE `certifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `certifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `communication_reports`
--

DROP TABLE IF EXISTS `communication_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `communication_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reportable_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reportable_id` bigint unsigned NOT NULL,
  `reporter_id` bigint unsigned NOT NULL,
  `reported_user_id` bigint unsigned NOT NULL,
  `reason` enum('harassment','spam','inappropriate','threatening','pii_sharing','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','investigating','resolved','dismissed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `resolved_by` bigint unsigned DEFAULT NULL,
  `resolution_notes` text COLLATE utf8mb4_unicode_ci,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `communication_reports_resolved_by_foreign` (`resolved_by`),
  KEY `communication_reports_reportable_type_reportable_id_index` (`reportable_type`,`reportable_id`),
  KEY `communication_reports_reporter_id_index` (`reporter_id`),
  KEY `communication_reports_reported_user_id_index` (`reported_user_id`),
  KEY `communication_reports_status_index` (`status`),
  CONSTRAINT `communication_reports_reported_user_id_foreign` FOREIGN KEY (`reported_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `communication_reports_reporter_id_foreign` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `communication_reports_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `communication_reports`
--

LOCK TABLES `communication_reports` WRITE;
/*!40000 ALTER TABLE `communication_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `communication_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `communication_templates`
--

DROP TABLE IF EXISTS `communication_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `communication_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('shift_instruction','welcome','reminder','thank_you','feedback_request','custom') COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` enum('email','sms','in_app','all') COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `variables` json DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `usage_count` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `communication_templates_business_id_slug_unique` (`business_id`,`slug`),
  KEY `communication_templates_business_id_type_index` (`business_id`,`type`),
  KEY `communication_templates_business_id_is_active_index` (`business_id`,`is_active`),
  CONSTRAINT `communication_templates_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `communication_templates`
--

LOCK TABLES `communication_templates` WRITE;
/*!40000 ALTER TABLE `communication_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `communication_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compliance_alerts`
--

DROP TABLE IF EXISTS `compliance_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `compliance_alerts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `alert_type` enum('payment_failure','high_dispute_rate','suspicious_activity','tax_compliance','license_expiry','background_check_expiry') COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` enum('info','warning','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'warning',
  `alertable_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alertable_id` bigint unsigned NOT NULL,
  `alert_message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `alert_data` json DEFAULT NULL,
  `acknowledged` tinyint(1) NOT NULL DEFAULT '0',
  `acknowledged_by` bigint unsigned DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `resolved` tinyint(1) NOT NULL DEFAULT '0',
  `resolution_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `compliance_alerts_alertable_type_alertable_id_index` (`alertable_type`,`alertable_id`),
  KEY `compliance_alerts_alert_type_severity_index` (`alert_type`,`severity`),
  KEY `compliance_alerts_acknowledged_resolved_index` (`acknowledged`,`resolved`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compliance_alerts`
--

LOCK TABLES `compliance_alerts` WRITE;
/*!40000 ALTER TABLE `compliance_alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `compliance_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compliance_report_access_logs`
--

DROP TABLE IF EXISTS `compliance_report_access_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `compliance_report_access_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `compliance_report_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `action` enum('view','download','export','email') COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `accessed_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `compliance_report_access_logs_compliance_report_id_index` (`compliance_report_id`),
  KEY `compliance_report_access_logs_user_id_index` (`user_id`),
  KEY `compliance_report_access_logs_accessed_at_index` (`accessed_at`),
  CONSTRAINT `compliance_report_access_logs_compliance_report_id_foreign` FOREIGN KEY (`compliance_report_id`) REFERENCES `compliance_reports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `compliance_report_access_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compliance_report_access_logs`
--

LOCK TABLES `compliance_report_access_logs` WRITE;
/*!40000 ALTER TABLE `compliance_report_access_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `compliance_report_access_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compliance_reports`
--

DROP TABLE IF EXISTS `compliance_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `compliance_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_type` enum('daily_financial_reconciliation','monthly_vat_summary','quarterly_worker_classification','annual_tax_summary','payment_audit','worker_hours_summary') COLLATE utf8mb4_unicode_ci NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `period_label` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','generating','completed','failed') COLLATE utf8mb4_unicode_ci NOT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `report_data` json DEFAULT NULL,
  `summary_stats` json DEFAULT NULL,
  `file_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_format` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pdf',
  `file_size` int DEFAULT NULL,
  `generated_by_user_id` bigint unsigned DEFAULT NULL,
  `generated_at` timestamp NULL DEFAULT NULL,
  `generation_time_seconds` int DEFAULT NULL,
  `download_count` int NOT NULL DEFAULT '0',
  `last_downloaded_at` timestamp NULL DEFAULT NULL,
  `last_downloaded_by_user_id` bigint unsigned DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `compliance_reports_generated_by_user_id_foreign` (`generated_by_user_id`),
  KEY `compliance_reports_last_downloaded_by_user_id_foreign` (`last_downloaded_by_user_id`),
  KEY `compliance_reports_report_type_period_start_period_end_index` (`report_type`,`period_start`,`period_end`),
  KEY `compliance_reports_status_index` (`status`),
  KEY `compliance_reports_generated_at_index` (`generated_at`),
  KEY `compliance_reports_is_archived_index` (`is_archived`),
  CONSTRAINT `compliance_reports_generated_by_user_id_foreign` FOREIGN KEY (`generated_by_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `compliance_reports_last_downloaded_by_user_id_foreign` FOREIGN KEY (`last_downloaded_by_user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compliance_reports`
--

LOCK TABLES `compliance_reports` WRITE;
/*!40000 ALTER TABLE `compliance_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `compliance_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compliance_violations`
--

DROP TABLE IF EXISTS `compliance_violations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `compliance_violations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `shift_id` bigint unsigned DEFAULT NULL,
  `labor_law_rule_id` bigint unsigned NOT NULL,
  `violation_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `violation_data` json DEFAULT NULL,
  `severity` enum('info','warning','violation','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'warning',
  `status` enum('detected','acknowledged','resolved','exempted','appealed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'detected',
  `was_blocked` tinyint(1) NOT NULL DEFAULT '0',
  `worker_notified` tinyint(1) NOT NULL DEFAULT '0',
  `business_notified` tinyint(1) NOT NULL DEFAULT '0',
  `resolution_notes` text COLLATE utf8mb4_unicode_ci,
  `resolved_by` bigint unsigned DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `compliance_violations_resolved_by_foreign` (`resolved_by`),
  KEY `compliance_violations_user_id_index` (`user_id`),
  KEY `compliance_violations_shift_id_index` (`shift_id`),
  KEY `compliance_violations_labor_law_rule_id_index` (`labor_law_rule_id`),
  KEY `compliance_violations_violation_code_index` (`violation_code`),
  KEY `compliance_violations_severity_index` (`severity`),
  KEY `compliance_violations_status_index` (`status`),
  KEY `compliance_violations_user_id_status_index` (`user_id`,`status`),
  KEY `compliance_violations_created_at_index` (`created_at`),
  CONSTRAINT `compliance_violations_labor_law_rule_id_foreign` FOREIGN KEY (`labor_law_rule_id`) REFERENCES `labor_law_rules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `compliance_violations_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `compliance_violations_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `compliance_violations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compliance_violations`
--

LOCK TABLES `compliance_violations` WRITE;
/*!40000 ALTER TABLE `compliance_violations` DISABLE KEYS */;
/*!40000 ALTER TABLE `compliance_violations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `confirmation_reminders`
--

DROP TABLE IF EXISTS `confirmation_reminders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `confirmation_reminders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_confirmation_id` bigint unsigned NOT NULL,
  `type` enum('email','sms','push') COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient_type` enum('worker','business') COLLATE utf8mb4_unicode_ci NOT NULL,
  `sent_at` timestamp NOT NULL,
  `delivered` tinyint(1) NOT NULL DEFAULT '0',
  `delivered_at` timestamp NULL DEFAULT NULL,
  `failure_reason` text COLLATE utf8mb4_unicode_ci,
  `notification_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conf_reminders_conf_id_type_idx` (`booking_confirmation_id`,`type`),
  KEY `conf_reminders_conf_id_recipient_idx` (`booking_confirmation_id`,`recipient_type`),
  KEY `confirmation_reminders_type_index` (`type`),
  KEY `confirmation_reminders_recipient_type_index` (`recipient_type`),
  CONSTRAINT `confirmation_reminders_booking_confirmation_id_foreign` FOREIGN KEY (`booking_confirmation_id`) REFERENCES `booking_confirmations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `confirmation_reminders`
--

LOCK TABLES `confirmation_reminders` WRITE;
/*!40000 ALTER TABLE `confirmation_reminders` DISABLE KEYS */;
/*!40000 ALTER TABLE `confirmation_reminders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `consent_records`
--

DROP TABLE IF EXISTS `consent_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `consent_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `session_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `consent_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `consented` tinyint(1) NOT NULL DEFAULT '0',
  `ip_address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `consent_details` json DEFAULT NULL,
  `consent_version` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `consent_source` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `consented_at` timestamp NULL DEFAULT NULL,
  `withdrawn_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `consent_records_user_id_consent_type_index` (`user_id`,`consent_type`),
  KEY `consent_records_session_id_consent_type_index` (`session_id`,`consent_type`),
  KEY `consent_records_consent_type_index` (`consent_type`),
  CONSTRAINT `consent_records_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consent_records`
--

LOCK TABLES `consent_records` WRITE;
/*!40000 ALTER TABLE `consent_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `consent_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conversation_participants`
--

DROP TABLE IF EXISTS `conversation_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversation_participants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `role` enum('owner','participant','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'participant',
  `last_read_at` timestamp NULL DEFAULT NULL,
  `is_muted` tinyint(1) NOT NULL DEFAULT '0',
  `left_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `conversation_participants_conversation_id_user_id_unique` (`conversation_id`,`user_id`),
  KEY `conversation_participants_user_id_left_at_index` (`user_id`,`left_at`),
  KEY `conversation_participants_conversation_id_role_index` (`conversation_id`,`role`),
  CONSTRAINT `conversation_participants_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversation_participants_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversation_participants`
--

LOCK TABLES `conversation_participants` WRITE;
/*!40000 ALTER TABLE `conversation_participants` DISABLE KEYS */;
/*!40000 ALTER TABLE `conversation_participants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conversations`
--

DROP TABLE IF EXISTS `conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('direct','shift','support','broadcast') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'direct',
  `shift_id` bigint unsigned DEFAULT NULL,
  `worker_id` bigint unsigned NOT NULL,
  `business_id` bigint unsigned NOT NULL,
  `subject` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','archived','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `is_archived` tinyint(1) NOT NULL DEFAULT '0',
  `last_message_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_conversation` (`worker_id`,`business_id`,`shift_id`),
  KEY `conversations_worker_id_business_id_index` (`worker_id`,`business_id`),
  KEY `conversations_shift_id_index` (`shift_id`),
  KEY `conversations_status_index` (`status`),
  KEY `conversations_last_message_at_index` (`last_message_at`),
  KEY `conversations_type_index` (`type`),
  KEY `conversations_is_archived_index` (`is_archived`),
  KEY `idx_conversations_participants` (`worker_id`,`business_id`,`is_archived`),
  KEY `idx_conversations_recent` (`last_message_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversations`
--

LOCK TABLES `conversations` WRITE;
/*!40000 ALTER TABLE `conversations` DISABLE KEYS */;
/*!40000 ALTER TABLE `conversations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `countries`
--

DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `countries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `country_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `currency_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `countries_country_code_unique` (`country_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `countries`
--

LOCK TABLES `countries` WRITE;
/*!40000 ALTER TABLE `countries` DISABLE KEYS */;
/*!40000 ALTER TABLE `countries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `credit_invoice_items`
--

DROP TABLE IF EXISTS `credit_invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_invoice_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `shift_id` bigint unsigned DEFAULT NULL,
  `shift_payment_id` bigint unsigned DEFAULT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `service_date` date NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `unit_price` decimal(10,2) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `credit_invoice_items_shift_payment_id_foreign` (`shift_payment_id`),
  KEY `credit_invoice_items_invoice_id_index` (`invoice_id`),
  KEY `credit_invoice_items_shift_id_index` (`shift_id`),
  KEY `credit_invoice_items_service_date_index` (`service_date`),
  CONSTRAINT `credit_invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `credit_invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `credit_invoice_items_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `credit_invoice_items_shift_payment_id_foreign` FOREIGN KEY (`shift_payment_id`) REFERENCES `shift_payments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_invoice_items`
--

LOCK TABLES `credit_invoice_items` WRITE;
/*!40000 ALTER TABLE `credit_invoice_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `credit_invoice_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `credit_invoices`
--

DROP TABLE IF EXISTS `credit_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `invoice_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `late_fees` decimal(10,2) NOT NULL DEFAULT '0.00',
  `adjustments` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(12,2) NOT NULL,
  `amount_paid` decimal(12,2) NOT NULL DEFAULT '0.00',
  `amount_due` decimal(12,2) NOT NULL,
  `status` enum('draft','issued','sent','partially_paid','paid','overdue','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `pdf_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pdf_generated_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `credit_invoices_invoice_number_unique` (`invoice_number`),
  KEY `credit_invoices_business_id_index` (`business_id`),
  KEY `credit_invoices_business_id_status_index` (`business_id`,`status`),
  KEY `credit_invoices_due_date_index` (`due_date`),
  KEY `credit_invoices_status_due_date_index` (`status`,`due_date`),
  KEY `credit_invoices_period_start_period_end_index` (`period_start`,`period_end`),
  KEY `credit_invoices_status_index` (`status`),
  CONSTRAINT `credit_invoices_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_invoices`
--

LOCK TABLES `credit_invoices` WRITE;
/*!40000 ALTER TABLE `credit_invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `credit_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cross_border_transfers`
--

DROP TABLE IF EXISTS `cross_border_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cross_border_transfers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transfer_reference` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `bank_account_id` bigint unsigned NOT NULL,
  `source_currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `destination_currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_amount` decimal(15,2) NOT NULL,
  `destination_amount` decimal(15,2) NOT NULL,
  `exchange_rate` decimal(15,8) NOT NULL,
  `fee_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_method` enum('sepa','swift','ach','faster_payments','local') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','processing','sent','completed','failed','returned') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `estimated_arrival_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `provider_reference` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `failure_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cross_border_transfers_transfer_reference_unique` (`transfer_reference`),
  KEY `cross_border_transfers_bank_account_id_foreign` (`bank_account_id`),
  KEY `cross_border_transfers_user_id_status_index` (`user_id`,`status`),
  KEY `cross_border_transfers_transfer_reference_index` (`transfer_reference`),
  KEY `cross_border_transfers_status_index` (`status`),
  CONSTRAINT `cross_border_transfers_bank_account_id_foreign` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cross_border_transfers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cross_border_transfers`
--

LOCK TABLES `cross_border_transfers` WRITE;
/*!40000 ALTER TABLE `cross_border_transfers` DISABLE KEYS */;
/*!40000 ALTER TABLE `cross_border_transfers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `currency_conversions`
--

DROP TABLE IF EXISTS `currency_conversions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `currency_conversions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `from_currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `to_currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_amount` decimal(15,2) NOT NULL,
  `to_amount` decimal(15,2) NOT NULL,
  `exchange_rate` decimal(15,8) NOT NULL,
  `fee_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `reference_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'completed',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `currency_conversions_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `currency_conversions_from_currency_to_currency_index` (`from_currency`,`to_currency`),
  KEY `currency_conversions_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  CONSTRAINT `currency_conversions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `currency_conversions`
--

LOCK TABLES `currency_conversions` WRITE;
/*!40000 ALTER TABLE `currency_conversions` DISABLE KEYS */;
/*!40000 ALTER TABLE `currency_conversions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `currency_wallets`
--

DROP TABLE IF EXISTS `currency_wallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `currency_wallets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `currency_code` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `pending_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `currency_wallets_user_id_currency_code_unique` (`user_id`,`currency_code`),
  KEY `currency_wallets_currency_code_index` (`currency_code`),
  CONSTRAINT `currency_wallets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `currency_wallets`
--

LOCK TABLES `currency_wallets` WRITE;
/*!40000 ALTER TABLE `currency_wallets` DISABLE KEYS */;
/*!40000 ALTER TABLE `currency_wallets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `data_regions`
--

DROP TABLE IF EXISTS `data_regions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `data_regions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `countries` json NOT NULL,
  `primary_storage` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `backup_storage` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `compliance_frameworks` json NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `data_regions_code_unique` (`code`),
  KEY `data_regions_code_index` (`code`),
  KEY `data_regions_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `data_regions`
--

LOCK TABLES `data_regions` WRITE;
/*!40000 ALTER TABLE `data_regions` DISABLE KEYS */;
/*!40000 ALTER TABLE `data_regions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `data_retention_policies`
--

DROP TABLE IF EXISTS `data_retention_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `data_retention_policies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `data_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_class` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `retention_days` int NOT NULL,
  `action` enum('delete','anonymize','archive') COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `description` text COLLATE utf8mb4_unicode_ci,
  `conditions` json DEFAULT NULL,
  `last_executed_at` timestamp NULL DEFAULT NULL,
  `last_affected_count` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `data_retention_policies_data_type_model_class_unique` (`data_type`,`model_class`),
  KEY `data_retention_policies_data_type_is_active_index` (`data_type`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `data_retention_policies`
--

LOCK TABLES `data_retention_policies` WRITE;
/*!40000 ALTER TABLE `data_retention_policies` DISABLE KEYS */;
/*!40000 ALTER TABLE `data_retention_policies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `data_subject_requests`
--

DROP TABLE IF EXISTS `data_subject_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `data_subject_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `request_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('access','rectification','erasure','portability','restriction','objection') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','verifying','processing','completed','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `description` text COLLATE utf8mb4_unicode_ci,
  `verification_token` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `due_date` timestamp NOT NULL,
  `assigned_to` bigint unsigned DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `completion_notes` text COLLATE utf8mb4_unicode_ci,
  `export_file_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `requester_ip` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requester_user_agent` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `data_subject_requests_request_number_unique` (`request_number`),
  KEY `data_subject_requests_assigned_to_foreign` (`assigned_to`),
  KEY `data_subject_requests_email_status_index` (`email`,`status`),
  KEY `data_subject_requests_status_due_date_index` (`status`,`due_date`),
  KEY `data_subject_requests_user_id_index` (`user_id`),
  CONSTRAINT `data_subject_requests_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
  CONSTRAINT `data_subject_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `data_subject_requests`
--

LOCK TABLES `data_subject_requests` WRITE;
/*!40000 ALTER TABLE `data_subject_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `data_subject_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `data_transfer_logs`
--

DROP TABLE IF EXISTS `data_transfer_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `data_transfer_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `from_region` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `to_region` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transfer_type` enum('migration','backup','export','processing') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','in_progress','completed','failed') COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_types` json NOT NULL,
  `legal_basis` text COLLATE utf8mb4_unicode_ci,
  `completed_at` timestamp NULL DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `data_transfer_logs_user_id_index` (`user_id`),
  KEY `data_transfer_logs_from_region_index` (`from_region`),
  KEY `data_transfer_logs_to_region_index` (`to_region`),
  KEY `data_transfer_logs_status_index` (`status`),
  KEY `data_transfer_logs_transfer_type_index` (`transfer_type`),
  KEY `data_transfer_logs_created_at_index` (`created_at`),
  CONSTRAINT `data_transfer_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `data_transfer_logs`
--

LOCK TABLES `data_transfer_logs` WRITE;
/*!40000 ALTER TABLE `data_transfer_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `data_transfer_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `demand_forecasts`
--

DROP TABLE IF EXISTS `demand_forecasts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `demand_forecasts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `forecast_date` date NOT NULL,
  `venue_id` bigint unsigned DEFAULT NULL,
  `skill_category` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `predicted_demand` int NOT NULL DEFAULT '0',
  `predicted_supply` int NOT NULL DEFAULT '0',
  `supply_demand_ratio` decimal(5,2) NOT NULL DEFAULT '0.00',
  `demand_level` enum('low','normal','high','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `factors` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `demand_forecasts_venue_id_foreign` (`venue_id`),
  KEY `demand_forecasts_forecast_date_region_index` (`forecast_date`,`region`),
  KEY `demand_forecasts_forecast_date_skill_category_index` (`forecast_date`,`skill_category`),
  KEY `demand_forecasts_demand_level_index` (`demand_level`),
  CONSTRAINT `demand_forecasts_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `demand_forecasts`
--

LOCK TABLES `demand_forecasts` WRITE;
/*!40000 ALTER TABLE `demand_forecasts` DISABLE KEYS */;
/*!40000 ALTER TABLE `demand_forecasts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `demand_metrics`
--

DROP TABLE IF EXISTS `demand_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `demand_metrics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `region` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `skill_category` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metric_date` date NOT NULL,
  `shifts_posted` int NOT NULL DEFAULT '0',
  `shifts_filled` int NOT NULL DEFAULT '0',
  `workers_available` int NOT NULL DEFAULT '0',
  `fill_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `supply_demand_ratio` decimal(5,2) NOT NULL DEFAULT '1.00',
  `calculated_surge` decimal(3,2) NOT NULL DEFAULT '1.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `demand_metrics_unique` (`region`,`skill_category`,`metric_date`),
  KEY `demand_metrics_metric_date_region_index` (`metric_date`,`region`),
  KEY `demand_metrics_skill_category_metric_date_index` (`skill_category`,`metric_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `demand_metrics`
--

LOCK TABLES `demand_metrics` WRITE;
/*!40000 ALTER TABLE `demand_metrics` DISABLE KEYS */;
/*!40000 ALTER TABLE `demand_metrics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `device_fingerprints`
--

DROP TABLE IF EXISTS `device_fingerprints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `device_fingerprints` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `fingerprint_hash` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fingerprint_data` json DEFAULT NULL,
  `ip_address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `use_count` int NOT NULL DEFAULT '1',
  `is_trusted` tinyint(1) NOT NULL DEFAULT '0',
  `is_blocked` tinyint(1) NOT NULL DEFAULT '0',
  `first_seen_at` timestamp NOT NULL,
  `last_seen_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `device_fingerprints_fingerprint_hash_index` (`fingerprint_hash`),
  KEY `device_fingerprints_user_id_fingerprint_hash_index` (`user_id`,`fingerprint_hash`),
  KEY `device_fingerprints_is_trusted_index` (`is_trusted`),
  KEY `device_fingerprints_is_blocked_index` (`is_blocked`),
  CONSTRAINT `device_fingerprints_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `device_fingerprints`
--

LOCK TABLES `device_fingerprints` WRITE;
/*!40000 ALTER TABLE `device_fingerprints` DISABLE KEYS */;
/*!40000 ALTER TABLE `device_fingerprints` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dispute_escalations`
--

DROP TABLE IF EXISTS `dispute_escalations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dispute_escalations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dispute_id` bigint unsigned NOT NULL,
  `escalation_level` tinyint NOT NULL DEFAULT '1',
  `escalation_reason` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `escalated_from_admin_id` bigint unsigned DEFAULT NULL,
  `escalated_to_admin_id` bigint unsigned DEFAULT NULL,
  `sla_hours_at_escalation` double DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `escalated_at` timestamp NOT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dispute_escalations_dispute_id_escalation_level_index` (`dispute_id`,`escalation_level`),
  KEY `dispute_escalations_escalated_at_index` (`escalated_at`),
  CONSTRAINT `dispute_escalations_dispute_id_foreign` FOREIGN KEY (`dispute_id`) REFERENCES `admin_dispute_queue` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dispute_escalations`
--

LOCK TABLES `dispute_escalations` WRITE;
/*!40000 ALTER TABLE `dispute_escalations` DISABLE KEYS */;
/*!40000 ALTER TABLE `dispute_escalations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dispute_messages`
--

DROP TABLE IF EXISTS `dispute_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dispute_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dispute_id` bigint unsigned NOT NULL,
  `sender_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sender_id` bigint unsigned NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `message_type` enum('text','evidence','system','resolution') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `is_internal` tinyint(1) NOT NULL DEFAULT '0',
  `attachments` json DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `read_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dispute_messages_sender_type_sender_id_index` (`sender_type`,`sender_id`),
  KEY `dispute_messages_dispute_id_created_at_index` (`dispute_id`,`created_at`),
  KEY `dispute_messages_message_type_index` (`message_type`),
  KEY `dispute_messages_is_internal_index` (`is_internal`),
  CONSTRAINT `dispute_messages_dispute_id_foreign` FOREIGN KEY (`dispute_id`) REFERENCES `admin_dispute_queue` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dispute_messages`
--

LOCK TABLES `dispute_messages` WRITE;
/*!40000 ALTER TABLE `dispute_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `dispute_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dispute_timeline`
--

DROP TABLE IF EXISTS `dispute_timeline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dispute_timeline` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dispute_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dispute_timeline_user_id_foreign` (`user_id`),
  KEY `dispute_timeline_dispute_id_created_at_index` (`dispute_id`,`created_at`),
  KEY `dispute_timeline_action_index` (`action`),
  CONSTRAINT `dispute_timeline_dispute_id_foreign` FOREIGN KEY (`dispute_id`) REFERENCES `disputes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dispute_timeline_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dispute_timeline`
--

LOCK TABLES `dispute_timeline` WRITE;
/*!40000 ALTER TABLE `dispute_timeline` DISABLE KEYS */;
/*!40000 ALTER TABLE `dispute_timeline` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `disputes`
--

DROP TABLE IF EXISTS `disputes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `disputes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shift_id` bigint unsigned NOT NULL,
  `worker_id` bigint unsigned NOT NULL,
  `business_id` bigint unsigned NOT NULL,
  `type` enum('payment','hours','deduction','bonus','expenses','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('open','under_review','awaiting_evidence','mediation','resolved','escalated','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `disputed_amount` decimal(10,2) NOT NULL,
  `worker_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `business_response` text COLLATE utf8mb4_unicode_ci,
  `assigned_to` bigint unsigned DEFAULT NULL,
  `admin_queue_id` bigint unsigned DEFAULT NULL,
  `evidence_worker` json DEFAULT NULL,
  `evidence_business` json DEFAULT NULL,
  `resolution` enum('worker_favor','business_favor','split','withdrawn','expired') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resolution_amount` decimal(10,2) DEFAULT NULL,
  `resolution_notes` text COLLATE utf8mb4_unicode_ci,
  `evidence_deadline` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `disputes_status_created_at_index` (`status`,`created_at`),
  KEY `disputes_worker_id_status_index` (`worker_id`,`status`),
  KEY `disputes_business_id_status_index` (`business_id`,`status`),
  KEY `disputes_shift_id_index` (`shift_id`),
  KEY `disputes_assigned_to_index` (`assigned_to`),
  KEY `disputes_type_index` (`type`),
  KEY `disputes_evidence_deadline_index` (`evidence_deadline`),
  KEY `disputes_admin_queue_id_index` (`admin_queue_id`),
  CONSTRAINT `disputes_admin_queue_id_foreign` FOREIGN KEY (`admin_queue_id`) REFERENCES `admin_dispute_queue` (`id`) ON DELETE SET NULL,
  CONSTRAINT `disputes_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `disputes_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `disputes_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `disputes_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `disputes`
--

LOCK TABLES `disputes` WRITE;
/*!40000 ALTER TABLE `disputes` DISABLE KEYS */;
/*!40000 ALTER TABLE `disputes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `earnings_summaries`
--

DROP TABLE IF EXISTS `earnings_summaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `earnings_summaries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `period_type` enum('daily','weekly','monthly','yearly') COLLATE utf8mb4_unicode_ci NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `shifts_completed` int NOT NULL DEFAULT '0',
  `total_hours` decimal(8,2) NOT NULL DEFAULT '0.00',
  `gross_earnings` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_fees` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_taxes` decimal(10,2) NOT NULL DEFAULT '0.00',
  `net_earnings` decimal(12,2) NOT NULL DEFAULT '0.00',
  `avg_hourly_rate` decimal(8,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `earnings_summaries_unique` (`user_id`,`period_type`,`period_start`),
  KEY `earnings_summaries_user_id_period_type_index` (`user_id`,`period_type`),
  KEY `earnings_summaries_period_start_index` (`period_start`),
  KEY `earnings_summaries_period_end_index` (`period_end`),
  CONSTRAINT `earnings_summaries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `earnings_summaries`
--

LOCK TABLES `earnings_summaries` WRITE;
/*!40000 ALTER TABLE `earnings_summaries` DISABLE KEYS */;
/*!40000 ALTER TABLE `earnings_summaries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_logs`
--

DROP TABLE IF EXISTS `email_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `to_email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `template_slug` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('queued','sent','delivered','opened','clicked','bounced','failed') COLLATE utf8mb4_unicode_ci NOT NULL,
  `message_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `opened_at` timestamp NULL DEFAULT NULL,
  `clicked_at` timestamp NULL DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `email_logs_user_id_foreign` (`user_id`),
  KEY `email_logs_to_email_index` (`to_email`),
  KEY `email_logs_template_slug_index` (`template_slug`),
  KEY `email_logs_status_index` (`status`),
  KEY `email_logs_message_id_index` (`message_id`),
  KEY `email_logs_sent_at_index` (`sent_at`),
  CONSTRAINT `email_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_logs`
--

LOCK TABLES `email_logs` WRITE;
/*!40000 ALTER TABLE `email_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_preferences`
--

DROP TABLE IF EXISTS `email_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_preferences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `marketing_emails` tinyint(1) NOT NULL DEFAULT '1',
  `shift_notifications` tinyint(1) NOT NULL DEFAULT '1',
  `payment_notifications` tinyint(1) NOT NULL DEFAULT '1',
  `weekly_digest` tinyint(1) NOT NULL DEFAULT '1',
  `tips_and_updates` tinyint(1) NOT NULL DEFAULT '1',
  `unsubscribe_token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_preferences_user_id_unique` (`user_id`),
  UNIQUE KEY `email_preferences_unsubscribe_token_unique` (`unsubscribe_token`),
  CONSTRAINT `email_preferences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_preferences`
--

LOCK TABLES `email_preferences` WRITE;
/*!40000 ALTER TABLE `email_preferences` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_preferences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_templates`
--

DROP TABLE IF EXISTS `email_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('transactional','marketing','notification','reminder') COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body_html` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `body_text` text COLLATE utf8mb4_unicode_ci,
  `variables` json NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_templates_slug_unique` (`slug`),
  KEY `email_templates_created_by_foreign` (`created_by`),
  KEY `email_templates_category_index` (`category`),
  KEY `email_templates_is_active_index` (`is_active`),
  CONSTRAINT `email_templates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_templates`
--

LOCK TABLES `email_templates` WRITE;
/*!40000 ALTER TABLE `email_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `emergency_alerts`
--

DROP TABLE IF EXISTS `emergency_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emergency_alerts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `alert_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `shift_id` bigint unsigned DEFAULT NULL,
  `venue_id` bigint unsigned DEFAULT NULL,
  `type` enum('sos','medical','safety','harassment','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sos',
  `status` enum('active','responded','resolved','false_alarm') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `location_address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `location_history` json DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `acknowledged_by` bigint unsigned DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolved_by` bigint unsigned DEFAULT NULL,
  `resolution_notes` text COLLATE utf8mb4_unicode_ci,
  `emergency_services_called` tinyint(1) NOT NULL DEFAULT '0',
  `emergency_contacts_notified` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `emergency_alerts_alert_number_unique` (`alert_number`),
  KEY `emergency_alerts_acknowledged_by_foreign` (`acknowledged_by`),
  KEY `emergency_alerts_resolved_by_foreign` (`resolved_by`),
  KEY `emergency_alerts_status_created_at_index` (`status`,`created_at`),
  KEY `emergency_alerts_user_id_status_index` (`user_id`,`status`),
  KEY `emergency_alerts_shift_id_status_index` (`shift_id`,`status`),
  KEY `emergency_alerts_venue_id_status_index` (`venue_id`,`status`),
  KEY `emergency_alerts_type_index` (`type`),
  CONSTRAINT `emergency_alerts_acknowledged_by_foreign` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `emergency_alerts_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `emergency_alerts_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `emergency_alerts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `emergency_alerts_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `emergency_alerts`
--

LOCK TABLES `emergency_alerts` WRITE;
/*!40000 ALTER TABLE `emergency_alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `emergency_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `emergency_contacts`
--

DROP TABLE IF EXISTS `emergency_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emergency_contacts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `relationship` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `verification_code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `priority` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `emergency_contacts_user_id_is_primary_index` (`user_id`,`is_primary`),
  KEY `emergency_contacts_user_id_priority_index` (`user_id`,`priority`),
  KEY `emergency_contacts_user_id_is_verified_index` (`user_id`,`is_verified`),
  CONSTRAINT `emergency_contacts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `emergency_contacts`
--

LOCK TABLES `emergency_contacts` WRITE;
/*!40000 ALTER TABLE `emergency_contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `emergency_contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exchange_rates`
--

DROP TABLE IF EXISTS `exchange_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exchange_rates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `base_currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rate` decimal(15,8) NOT NULL,
  `inverse_rate` decimal(15,8) NOT NULL,
  `source` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ecb',
  `rate_date` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exchange_rates_base_currency_target_currency_rate_date_unique` (`base_currency`,`target_currency`,`rate_date`),
  KEY `exchange_rates_base_currency_target_currency_index` (`base_currency`,`target_currency`),
  KEY `exchange_rates_rate_date_index` (`rate_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exchange_rates`
--

LOCK TABLES `exchange_rates` WRITE;
/*!40000 ALTER TABLE `exchange_rates` DISABLE KEYS */;
/*!40000 ALTER TABLE `exchange_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `face_profiles`
--

DROP TABLE IF EXISTS `face_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `face_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `face_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Provider face ID from AWS/Azure',
  `provider` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aws' COMMENT 'aws, azure, faceplusplus',
  `face_attributes` json DEFAULT NULL COMMENT 'Age, gender confidence, etc.',
  `photo_count` int NOT NULL DEFAULT '0' COMMENT 'Number of enrolled photos',
  `is_enrolled` tinyint(1) NOT NULL DEFAULT '0',
  `enrolled_at` timestamp NULL DEFAULT NULL,
  `last_verified_at` timestamp NULL DEFAULT NULL,
  `verification_count` int NOT NULL DEFAULT '0',
  `avg_confidence` decimal(5,2) DEFAULT NULL COMMENT 'Average confidence score',
  `enrollment_image_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Primary enrolled face image',
  `additional_images` json DEFAULT NULL COMMENT 'Array of additional enrolled images',
  `collection_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'AWS Rekognition collection ID',
  `status` enum('pending','active','suspended','deleted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `face_profiles_user_id_unique` (`user_id`),
  KEY `face_profiles_provider_is_enrolled_index` (`provider`,`is_enrolled`),
  KEY `face_profiles_face_id_index` (`face_id`),
  CONSTRAINT `face_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `face_profiles`
--

LOCK TABLES `face_profiles` WRITE;
/*!40000 ALTER TABLE `face_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `face_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `face_verification_logs`
--

DROP TABLE IF EXISTS `face_verification_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `face_verification_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `shift_id` bigint unsigned DEFAULT NULL,
  `shift_assignment_id` bigint unsigned DEFAULT NULL,
  `action` enum('enroll','verify_clock_in','verify_clock_out','re_verify','manual_override') COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'aws, azure, faceplusplus, manual',
  `confidence_score` decimal(5,2) DEFAULT NULL COMMENT '0-100 confidence percentage',
  `liveness_passed` tinyint(1) DEFAULT NULL,
  `match_result` tinyint(1) DEFAULT NULL,
  `source_image_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Image used for verification',
  `provider_response` json DEFAULT NULL COMMENT 'Raw response from provider',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_info` text COLLATE utf8mb4_unicode_ci,
  `failure_reason` text COLLATE utf8mb4_unicode_ci,
  `processing_time_ms` int DEFAULT NULL COMMENT 'Time taken for verification',
  `face_attributes` json DEFAULT NULL COMMENT 'Detected face attributes',
  `fallback_used` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Manual verification fallback',
  `approved_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `face_verification_logs_shift_assignment_id_foreign` (`shift_assignment_id`),
  KEY `face_verification_logs_approved_by_foreign` (`approved_by`),
  KEY `face_verification_logs_user_id_action_index` (`user_id`,`action`),
  KEY `face_verification_logs_shift_id_action_index` (`shift_id`,`action`),
  KEY `face_verification_logs_created_at_index` (`created_at`),
  KEY `face_verification_logs_match_result_liveness_passed_index` (`match_result`,`liveness_passed`),
  CONSTRAINT `face_verification_logs_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `face_verification_logs_shift_assignment_id_foreign` FOREIGN KEY (`shift_assignment_id`) REFERENCES `shift_assignments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `face_verification_logs_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `face_verification_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `face_verification_logs`
--

LOCK TABLES `face_verification_logs` WRITE;
/*!40000 ALTER TABLE `face_verification_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `face_verification_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `favourite_workers`
--

DROP TABLE IF EXISTS `favourite_workers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `favourite_workers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `worker_id` bigint unsigned NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `favourite_workers_business_id_worker_id_unique` (`business_id`,`worker_id`),
  KEY `favourite_workers_worker_id_foreign` (`worker_id`),
  KEY `favourite_workers_business_id_index` (`business_id`),
  CONSTRAINT `favourite_workers_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `favourite_workers_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `favourite_workers`
--

LOCK TABLES `favourite_workers` WRITE;
/*!40000 ALTER TABLE `favourite_workers` DISABLE KEYS */;
/*!40000 ALTER TABLE `favourite_workers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feature_flag_logs`
--

DROP TABLE IF EXISTS `feature_flag_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feature_flag_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `feature_flag_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `action` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_value` json DEFAULT NULL,
  `new_value` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `feature_flag_logs_feature_flag_id_index` (`feature_flag_id`),
  KEY `feature_flag_logs_user_id_index` (`user_id`),
  KEY `feature_flag_logs_action_index` (`action`),
  CONSTRAINT `feature_flag_logs_feature_flag_id_foreign` FOREIGN KEY (`feature_flag_id`) REFERENCES `feature_flags` (`id`) ON DELETE CASCADE,
  CONSTRAINT `feature_flag_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feature_flag_logs`
--

LOCK TABLES `feature_flag_logs` WRITE;
/*!40000 ALTER TABLE `feature_flag_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `feature_flag_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feature_flags`
--

DROP TABLE IF EXISTS `feature_flags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feature_flags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `rollout_percentage` int NOT NULL DEFAULT '0',
  `enabled_for_users` json DEFAULT NULL,
  `enabled_for_roles` json DEFAULT NULL,
  `enabled_for_tiers` json DEFAULT NULL,
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `feature_flags_key_unique` (`key`),
  KEY `feature_flags_created_by_foreign` (`created_by`),
  KEY `feature_flags_is_enabled_index` (`is_enabled`),
  KEY `feature_flags_starts_at_ends_at_index` (`starts_at`,`ends_at`),
  CONSTRAINT `feature_flags_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feature_flags`
--

LOCK TABLES `feature_flags` WRITE;
/*!40000 ALTER TABLE `feature_flags` DISABLE KEYS */;
/*!40000 ALTER TABLE `feature_flags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feature_request_votes`
--

DROP TABLE IF EXISTS `feature_request_votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feature_request_votes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `feature_request_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `feature_request_votes_feature_request_id_user_id_unique` (`feature_request_id`,`user_id`),
  KEY `feature_request_votes_user_id_index` (`user_id`),
  CONSTRAINT `feature_request_votes_feature_request_id_foreign` FOREIGN KEY (`feature_request_id`) REFERENCES `feature_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `feature_request_votes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feature_request_votes`
--

LOCK TABLES `feature_request_votes` WRITE;
/*!40000 ALTER TABLE `feature_request_votes` DISABLE KEYS */;
/*!40000 ALTER TABLE `feature_request_votes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feature_requests`
--

DROP TABLE IF EXISTS `feature_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feature_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('ui','feature','integration','mobile','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'feature',
  `status` enum('submitted','under_review','planned','in_progress','completed','declined') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'submitted',
  `vote_count` int NOT NULL DEFAULT '0',
  `priority` int DEFAULT NULL,
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `feature_requests_status_vote_count_index` (`status`,`vote_count`),
  KEY `feature_requests_category_status_index` (`category`,`status`),
  KEY `feature_requests_user_id_index` (`user_id`),
  KEY `feature_requests_vote_count_index` (`vote_count`),
  CONSTRAINT `feature_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feature_requests`
--

LOCK TABLES `feature_requests` WRITE;
/*!40000 ALTER TABLE `feature_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `feature_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `first_shift_progress`
--

DROP TABLE IF EXISTS `first_shift_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `first_shift_progress` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_profile_id` bigint unsigned NOT NULL,
  `wizard_completed` tinyint(1) NOT NULL DEFAULT '0',
  `wizard_completed_at` timestamp NULL DEFAULT NULL,
  `current_step` tinyint unsigned NOT NULL DEFAULT '1',
  `highest_step_reached` tinyint unsigned NOT NULL DEFAULT '1',
  `step_1_venue_complete` tinyint(1) NOT NULL DEFAULT '0',
  `step_2_role_complete` tinyint(1) NOT NULL DEFAULT '0',
  `step_3_schedule_complete` tinyint(1) NOT NULL DEFAULT '0',
  `step_4_rate_complete` tinyint(1) NOT NULL DEFAULT '0',
  `step_5_details_complete` tinyint(1) NOT NULL DEFAULT '0',
  `step_6_review_complete` tinyint(1) NOT NULL DEFAULT '0',
  `step_1_completed_at` timestamp NULL DEFAULT NULL,
  `step_2_completed_at` timestamp NULL DEFAULT NULL,
  `step_3_completed_at` timestamp NULL DEFAULT NULL,
  `step_4_completed_at` timestamp NULL DEFAULT NULL,
  `step_5_completed_at` timestamp NULL DEFAULT NULL,
  `step_6_completed_at` timestamp NULL DEFAULT NULL,
  `draft_data` json DEFAULT NULL,
  `selected_venue_id` bigint unsigned DEFAULT NULL,
  `selected_role` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `selected_date` date DEFAULT NULL,
  `selected_start_time` time DEFAULT NULL,
  `selected_end_time` time DEFAULT NULL,
  `selected_hourly_rate` int DEFAULT NULL,
  `selected_workers_needed` int NOT NULL DEFAULT '1',
  `posting_mode` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'detailed',
  `save_as_template` tinyint(1) NOT NULL DEFAULT '0',
  `template_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_shift_id` bigint unsigned DEFAULT NULL,
  `total_time_spent_seconds` int NOT NULL DEFAULT '0',
  `session_count` int NOT NULL DEFAULT '0',
  `last_activity_at` timestamp NULL DEFAULT NULL,
  `promo_applied` tinyint(1) NOT NULL DEFAULT '0',
  `promo_code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `promo_discount_cents` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `first_shift_progress_selected_venue_id_foreign` (`selected_venue_id`),
  KEY `first_shift_progress_first_shift_id_foreign` (`first_shift_id`),
  KEY `first_shift_progress_business_profile_id_index` (`business_profile_id`),
  KEY `first_shift_progress_wizard_completed_index` (`wizard_completed`),
  KEY `first_shift_progress_current_step_index` (`current_step`),
  CONSTRAINT `first_shift_progress_business_profile_id_foreign` FOREIGN KEY (`business_profile_id`) REFERENCES `business_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `first_shift_progress_first_shift_id_foreign` FOREIGN KEY (`first_shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `first_shift_progress_selected_venue_id_foreign` FOREIGN KEY (`selected_venue_id`) REFERENCES `venues` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `first_shift_progress`
--

LOCK TABLES `first_shift_progress` WRITE;
/*!40000 ALTER TABLE `first_shift_progress` DISABLE KEYS */;
/*!40000 ALTER TABLE `first_shift_progress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fraud_rules`
--

DROP TABLE IF EXISTS `fraud_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fraud_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category` enum('velocity','device','location','behavior','identity','payment') COLLATE utf8mb4_unicode_ci NOT NULL,
  `conditions` json NOT NULL,
  `severity` int NOT NULL DEFAULT '5',
  `action` enum('flag','block','review','notify') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'flag',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fraud_rules_code_unique` (`code`),
  KEY `fraud_rules_category_index` (`category`),
  KEY `fraud_rules_is_active_index` (`is_active`),
  KEY `fraud_rules_severity_index` (`severity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fraud_rules`
--

LOCK TABLES `fraud_rules` WRITE;
/*!40000 ALTER TABLE `fraud_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `fraud_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fraud_signals`
--

DROP TABLE IF EXISTS `fraud_signals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fraud_signals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `signal_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `signal_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` int NOT NULL DEFAULT '1',
  `signal_data` json DEFAULT NULL,
  `ip_address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_fingerprint` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_resolved` tinyint(1) NOT NULL DEFAULT '0',
  `resolution_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fraud_signals_signal_type_index` (`signal_type`),
  KEY `fraud_signals_signal_code_index` (`signal_code`),
  KEY `fraud_signals_severity_index` (`severity`),
  KEY `fraud_signals_is_resolved_index` (`is_resolved`),
  KEY `fraud_signals_user_id_signal_type_index` (`user_id`,`signal_type`),
  KEY `fraud_signals_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `fraud_signals_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fraud_signals`
--

LOCK TABLES `fraud_signals` WRITE;
/*!40000 ALTER TABLE `fraud_signals` DISABLE KEYS */;
/*!40000 ALTER TABLE `fraud_signals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `health_declarations`
--

DROP TABLE IF EXISTS `health_declarations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `health_declarations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `shift_id` bigint unsigned DEFAULT NULL,
  `fever_free` tinyint(1) NOT NULL DEFAULT '1',
  `no_symptoms` tinyint(1) NOT NULL DEFAULT '1',
  `no_exposure` tinyint(1) NOT NULL DEFAULT '1',
  `fit_for_work` tinyint(1) NOT NULL DEFAULT '1',
  `declared_at` timestamp NOT NULL,
  `ip_address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `health_declarations_user_id_declared_at_index` (`user_id`,`declared_at`),
  KEY `health_declarations_shift_id_declared_at_index` (`shift_id`,`declared_at`),
  CONSTRAINT `health_declarations_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `health_declarations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `health_declarations`
--

LOCK TABLES `health_declarations` WRITE;
/*!40000 ALTER TABLE `health_declarations` DISABLE KEYS */;
/*!40000 ALTER TABLE `health_declarations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `holiday_calendars`
--

DROP TABLE IF EXISTS `holiday_calendars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `holiday_calendars` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned DEFAULT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `country_code` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `included_holidays` json DEFAULT NULL,
  `excluded_holidays` json DEFAULT NULL,
  `custom_dates` json DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `holiday_calendars_business_id_country_code_index` (`business_id`,`country_code`),
  KEY `holiday_calendars_country_code_index` (`country_code`),
  CONSTRAINT `holiday_calendars_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holiday_calendars`
--

LOCK TABLES `holiday_calendars` WRITE;
/*!40000 ALTER TABLE `holiday_calendars` DISABLE KEYS */;
/*!40000 ALTER TABLE `holiday_calendars` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `identity_verifications`
--

DROP TABLE IF EXISTS `identity_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `identity_verifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `provider` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'onfido',
  `provider_applicant_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_check_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_report_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','awaiting_input','processing','manual_review','approved','rejected','expired','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `verification_level` enum('basic','standard','enhanced') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'standard',
  `document_types` json DEFAULT NULL,
  `result` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `result_details` json DEFAULT NULL,
  `confidence_score` decimal(5,4) DEFAULT NULL,
  `sub_results` json DEFAULT NULL,
  `extracted_first_name` text COLLATE utf8mb4_unicode_ci,
  `extracted_last_name` text COLLATE utf8mb4_unicode_ci,
  `extracted_date_of_birth` text COLLATE utf8mb4_unicode_ci,
  `extracted_document_number` text COLLATE utf8mb4_unicode_ci,
  `extracted_expiry_date` date DEFAULT NULL,
  `extracted_nationality` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `extracted_gender` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `extracted_address` text COLLATE utf8mb4_unicode_ci,
  `face_match_performed` tinyint(1) NOT NULL DEFAULT '0',
  `face_match_score` decimal(5,4) DEFAULT NULL,
  `face_match_result` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jurisdiction_country` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `compliance_flags` json DEFAULT NULL,
  `aml_check_results` json DEFAULT NULL,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_notes` text COLLATE utf8mb4_unicode_ci,
  `rejection_reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rejection_details` json DEFAULT NULL,
  `attempt_count` int NOT NULL DEFAULT '1',
  `max_attempts` int NOT NULL DEFAULT '3',
  `last_attempt_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `reminder_sent_at` timestamp NULL DEFAULT NULL,
  `sdk_token` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sdk_token_expires_at` timestamp NULL DEFAULT NULL,
  `session_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_info` json DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `identity_verifications_reviewed_by_foreign` (`reviewed_by`),
  KEY `identity_verifications_user_id_index` (`user_id`),
  KEY `identity_verifications_status_index` (`status`),
  KEY `identity_verifications_provider_index` (`provider`),
  KEY `identity_verifications_provider_applicant_id_index` (`provider_applicant_id`),
  KEY `identity_verifications_provider_check_id_index` (`provider_check_id`),
  KEY `identity_verifications_verification_level_index` (`verification_level`),
  KEY `identity_verifications_expires_at_index` (`expires_at`),
  KEY `identity_verifications_created_at_index` (`created_at`),
  CONSTRAINT `identity_verifications_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `identity_verifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `identity_verifications`
--

LOCK TABLES `identity_verifications` WRITE;
/*!40000 ALTER TABLE `identity_verifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `identity_verifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `improvement_metrics`
--

DROP TABLE IF EXISTS `improvement_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `improvement_metrics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `metric_key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `current_value` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `target_value` decimal(15,4) DEFAULT NULL,
  `baseline_value` decimal(15,4) DEFAULT NULL,
  `trend` enum('up','down','stable') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'stable',
  `unit` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `history` json DEFAULT NULL,
  `measured_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `improvement_metrics_metric_key_unique` (`metric_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `improvement_metrics`
--

LOCK TABLES `improvement_metrics` WRITE;
/*!40000 ALTER TABLE `improvement_metrics` DISABLE KEYS */;
/*!40000 ALTER TABLE `improvement_metrics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `improvement_suggestions`
--

DROP TABLE IF EXISTS `improvement_suggestions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `improvement_suggestions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `submitted_by` bigint unsigned NOT NULL,
  `category` enum('feature','bug','ux','process','performance','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `expected_impact` text COLLATE utf8mb4_unicode_ci,
  `status` enum('submitted','under_review','approved','in_progress','completed','rejected','deferred') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'submitted',
  `votes` int NOT NULL DEFAULT '0',
  `assigned_to` bigint unsigned DEFAULT NULL,
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `improvement_suggestions_assigned_to_foreign` (`assigned_to`),
  KEY `improvement_suggestions_status_priority_index` (`status`,`priority`),
  KEY `improvement_suggestions_category_status_index` (`category`,`status`),
  KEY `improvement_suggestions_submitted_by_status_index` (`submitted_by`,`status`),
  CONSTRAINT `improvement_suggestions_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
  CONSTRAINT `improvement_suggestions_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `improvement_suggestions`
--

LOCK TABLES `improvement_suggestions` WRITE;
/*!40000 ALTER TABLE `improvement_suggestions` DISABLE KEYS */;
/*!40000 ALTER TABLE `improvement_suggestions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `incident_updates`
--

DROP TABLE IF EXISTS `incident_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `incident_updates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `incident_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachments` json DEFAULT NULL,
  `is_internal` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `incident_updates_incident_id_index` (`incident_id`),
  KEY `incident_updates_user_id_index` (`user_id`),
  KEY `incident_updates_is_internal_index` (`is_internal`),
  KEY `incident_updates_incident_id_created_at_index` (`incident_id`,`created_at`),
  CONSTRAINT `incident_updates_incident_id_foreign` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `incident_updates_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `incident_updates`
--

LOCK TABLES `incident_updates` WRITE;
/*!40000 ALTER TABLE `incident_updates` DISABLE KEYS */;
/*!40000 ALTER TABLE `incident_updates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `incidents`
--

DROP TABLE IF EXISTS `incidents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `incidents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `incident_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shift_id` bigint unsigned DEFAULT NULL,
  `venue_id` bigint unsigned DEFAULT NULL,
  `reported_by` bigint unsigned NOT NULL,
  `involves_user_id` bigint unsigned DEFAULT NULL,
  `type` enum('injury','harassment','theft','safety_hazard','property_damage','verbal_abuse','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_description` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `incident_time` timestamp NOT NULL,
  `evidence_urls` json DEFAULT NULL,
  `witness_info` json DEFAULT NULL,
  `status` enum('reported','investigating','resolved','escalated','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reported',
  `assigned_to` bigint unsigned DEFAULT NULL,
  `resolution_notes` text COLLATE utf8mb4_unicode_ci,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `requires_insurance_claim` tinyint(1) NOT NULL DEFAULT '0',
  `insurance_claim_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `authorities_notified` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `incidents_incident_number_unique` (`incident_number`),
  KEY `incidents_involves_user_id_foreign` (`involves_user_id`),
  KEY `incidents_incident_number_index` (`incident_number`),
  KEY `incidents_shift_id_index` (`shift_id`),
  KEY `incidents_venue_id_index` (`venue_id`),
  KEY `incidents_reported_by_index` (`reported_by`),
  KEY `incidents_type_index` (`type`),
  KEY `incidents_severity_index` (`severity`),
  KEY `incidents_status_index` (`status`),
  KEY `incidents_incident_time_index` (`incident_time`),
  KEY `incidents_assigned_to_index` (`assigned_to`),
  KEY `incidents_status_severity_index` (`status`,`severity`),
  KEY `incidents_type_status_index` (`type`,`status`),
  CONSTRAINT `incidents_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
  CONSTRAINT `incidents_involves_user_id_foreign` FOREIGN KEY (`involves_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `incidents_reported_by_foreign` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `incidents_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `incidents_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `incidents`
--

LOCK TABLES `incidents` WRITE;
/*!40000 ALTER TABLE `incidents` DISABLE KEYS */;
/*!40000 ALTER TABLE `incidents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `industries`
--

DROP TABLE IF EXISTS `industries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `industries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `naics_code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sic_code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `level` int NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `common_certifications` json DEFAULT NULL,
  `common_skills` json DEFAULT NULL,
  `compliance_requirements` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `business_count` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `industries_code_unique` (`code`),
  KEY `industries_parent_id_index` (`parent_id`),
  KEY `industries_is_active_index` (`is_active`),
  KEY `industries_sort_order_index` (`sort_order`),
  KEY `industries_naics_code_index` (`naics_code`),
  CONSTRAINT `industries_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `industries` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `industries`
--

LOCK TABLES `industries` WRITE;
/*!40000 ALTER TABLE `industries` DISABLE KEYS */;
/*!40000 ALTER TABLE `industries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `instapay_requests`
--

DROP TABLE IF EXISTS `instapay_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `instapay_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `shift_assignment_id` bigint unsigned DEFAULT NULL,
  `gross_amount` decimal(10,2) NOT NULL,
  `instapay_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `platform_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `net_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','completed','failed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payout_method` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payout_reference` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requested_at` timestamp NOT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `failure_reason` text COLLATE utf8mb4_unicode_ci,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EUR',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `instapay_requests_shift_assignment_id_foreign` (`shift_assignment_id`),
  KEY `instapay_requests_user_id_status_index` (`user_id`,`status`),
  KEY `instapay_requests_status_requested_at_index` (`status`,`requested_at`),
  KEY `instapay_requests_payout_reference_index` (`payout_reference`),
  CONSTRAINT `instapay_requests_shift_assignment_id_foreign` FOREIGN KEY (`shift_assignment_id`) REFERENCES `shift_assignments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `instapay_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `instapay_requests`
--

LOCK TABLES `instapay_requests` WRITE;
/*!40000 ALTER TABLE `instapay_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `instapay_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `instapay_settings`
--

DROP TABLE IF EXISTS `instapay_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `instapay_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `preferred_method` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'stripe',
  `minimum_amount` decimal(10,2) NOT NULL DEFAULT '10.00',
  `auto_request` tinyint(1) NOT NULL DEFAULT '0',
  `daily_cutoff` time NOT NULL DEFAULT '14:00:00',
  `daily_limit_override` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instapay_settings_user_id_unique` (`user_id`),
  CONSTRAINT `instapay_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `instapay_settings`
--

LOCK TABLES `instapay_settings` WRITE;
/*!40000 ALTER TABLE `instapay_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `instapay_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `insurance_carriers`
--

DROP TABLE IF EXISTS `insurance_carriers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `insurance_carriers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `naic_code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `am_best_rating` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `am_best_financial_size` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'US',
  `operating_regions` json DEFAULT NULL,
  `verification_api_endpoint` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verification_api_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supports_coi_verification` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `insurance_carriers_naic_code_unique` (`naic_code`),
  KEY `insurance_carriers_name_is_active_index` (`name`,`is_active`),
  KEY `insurance_carriers_naic_code_index` (`naic_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `insurance_carriers`
--

LOCK TABLES `insurance_carriers` WRITE;
/*!40000 ALTER TABLE `insurance_carriers` DISABLE KEYS */;
/*!40000 ALTER TABLE `insurance_carriers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `insurance_certificate_access_logs`
--

DROP TABLE IF EXISTS `insurance_certificate_access_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `insurance_certificate_access_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `insurance_certificate_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ical_cert_created_idx` (`insurance_certificate_id`,`created_at`),
  KEY `ical_user_action_idx` (`user_id`,`action`),
  CONSTRAINT `ical_cert_id_fk` FOREIGN KEY (`insurance_certificate_id`) REFERENCES `insurance_certificates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `insurance_certificate_access_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `insurance_certificate_access_logs`
--

LOCK TABLES `insurance_certificate_access_logs` WRITE;
/*!40000 ALTER TABLE `insurance_certificate_access_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `insurance_certificate_access_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `insurance_certificates`
--

DROP TABLE IF EXISTS `insurance_certificates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `insurance_certificates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `insurance_verification_id` bigint unsigned NOT NULL,
  `business_profile_id` bigint unsigned NOT NULL,
  `requirement_id` bigint unsigned DEFAULT NULL,
  `insurance_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `policy_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `carrier_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `carrier_naic_code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `carrier_am_best_rating` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `named_insured` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `insured_address` text COLLATE utf8mb4_unicode_ci,
  `coverage_amount` bigint unsigned NOT NULL,
  `coverage_currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `per_occurrence_limit` bigint unsigned DEFAULT NULL,
  `aggregate_limit` bigint unsigned DEFAULT NULL,
  `deductible_amount` bigint unsigned DEFAULT NULL,
  `coverage_details` json DEFAULT NULL,
  `effective_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `is_expired` tinyint(1) NOT NULL DEFAULT '0',
  `auto_renews` tinyint(1) NOT NULL DEFAULT '0',
  `has_additional_insured` tinyint(1) NOT NULL DEFAULT '0',
  `additional_insured_verified` tinyint(1) NOT NULL DEFAULT '0',
  `additional_insured_text` text COLLATE utf8mb4_unicode_ci,
  `has_waiver_of_subrogation` tinyint(1) NOT NULL DEFAULT '0',
  `waiver_verified` tinyint(1) NOT NULL DEFAULT '0',
  `file_path_encrypted` text COLLATE utf8mb4_unicode_ci,
  `file_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_filename` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint unsigned DEFAULT NULL,
  `storage_provider` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 's3',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `carrier_verified` tinyint(1) NOT NULL DEFAULT '0',
  `carrier_verified_at` timestamp NULL DEFAULT NULL,
  `carrier_verification_response` json DEFAULT NULL,
  `extracted_data` json DEFAULT NULL,
  `extraction_confidence` float DEFAULT NULL,
  `extracted_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_notes` text COLLATE utf8mb4_unicode_ci,
  `rejection_reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meets_minimum_coverage` tinyint(1) NOT NULL DEFAULT '0',
  `coverage_validation_details` json DEFAULT NULL,
  `access_token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `access_token_expires_at` timestamp NULL DEFAULT NULL,
  `download_count` int NOT NULL DEFAULT '0',
  `last_accessed_at` timestamp NULL DEFAULT NULL,
  `expiry_90_day_notified` tinyint(1) NOT NULL DEFAULT '0',
  `expiry_60_day_notified` tinyint(1) NOT NULL DEFAULT '0',
  `expiry_30_day_notified` tinyint(1) NOT NULL DEFAULT '0',
  `expiry_14_day_notified` tinyint(1) NOT NULL DEFAULT '0',
  `expiry_7_day_notified` tinyint(1) NOT NULL DEFAULT '0',
  `expired_notified` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `insurance_certificates_access_token_unique` (`access_token`),
  KEY `insurance_certificates_requirement_id_foreign` (`requirement_id`),
  KEY `insurance_certificates_reviewed_by_foreign` (`reviewed_by`),
  KEY `ic_verification_type_idx` (`insurance_verification_id`,`insurance_type`),
  KEY `ic_bp_status_idx` (`business_profile_id`,`status`),
  KEY `ic_status_expiry_idx` (`status`,`expiry_date`),
  KEY `ic_expiry_expired_idx` (`expiry_date`,`is_expired`),
  KEY `ic_access_token_idx` (`access_token`),
  KEY `ic_carrier_name_idx` (`carrier_name`),
  CONSTRAINT `insurance_certificates_business_profile_id_foreign` FOREIGN KEY (`business_profile_id`) REFERENCES `business_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `insurance_certificates_insurance_verification_id_foreign` FOREIGN KEY (`insurance_verification_id`) REFERENCES `insurance_verifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `insurance_certificates_requirement_id_foreign` FOREIGN KEY (`requirement_id`) REFERENCES `insurance_requirements` (`id`),
  CONSTRAINT `insurance_certificates_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `insurance_certificates`
--

LOCK TABLES `insurance_certificates` WRITE;
/*!40000 ALTER TABLE `insurance_certificates` DISABLE KEYS */;
/*!40000 ALTER TABLE `insurance_certificates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `insurance_requirements`
--

DROP TABLE IF EXISTS `insurance_requirements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `insurance_requirements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `jurisdiction` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `insurance_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `insurance_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_required` tinyint(1) NOT NULL DEFAULT '1',
  `is_jurisdiction_dependent` tinyint(1) NOT NULL DEFAULT '0',
  `required_in_regions` json DEFAULT NULL,
  `business_types` json DEFAULT NULL,
  `industries` json DEFAULT NULL,
  `minimum_coverage_amount` bigint unsigned DEFAULT NULL,
  `coverage_currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `minimum_per_occurrence` bigint unsigned DEFAULT NULL,
  `minimum_aggregate` bigint unsigned DEFAULT NULL,
  `additional_insured_required` tinyint(1) NOT NULL DEFAULT '0',
  `additional_insured_wording` text COLLATE utf8mb4_unicode_ci,
  `waiver_of_subrogation_required` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ir_jurisdiction_type_active_idx` (`jurisdiction`,`insurance_type`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `insurance_requirements`
--

LOCK TABLES `insurance_requirements` WRITE;
/*!40000 ALTER TABLE `insurance_requirements` DISABLE KEYS */;
/*!40000 ALTER TABLE `insurance_requirements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `insurance_verifications`
--

DROP TABLE IF EXISTS `insurance_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `insurance_verifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_profile_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `jurisdiction` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `is_fully_compliant` tinyint(1) NOT NULL DEFAULT '0',
  `compliant_since` timestamp NULL DEFAULT NULL,
  `last_compliance_check` timestamp NULL DEFAULT NULL,
  `compliance_summary` json DEFAULT NULL,
  `missing_coverages` json DEFAULT NULL,
  `expiring_soon` json DEFAULT NULL,
  `is_suspended` tinyint(1) NOT NULL DEFAULT '0',
  `suspended_at` timestamp NULL DEFAULT NULL,
  `suspension_reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `suspension_lifted_at` timestamp NULL DEFAULT NULL,
  `notification_history` json DEFAULT NULL,
  `last_reminder_sent_at` timestamp NULL DEFAULT NULL,
  `reminders_sent` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `insurance_verifications_user_id_foreign` (`user_id`),
  KEY `iv_bp_status_idx` (`business_profile_id`,`status`),
  KEY `iv_status_compliant_idx` (`status`,`is_fully_compliant`),
  KEY `iv_jurisdiction_idx` (`jurisdiction`),
  CONSTRAINT `insurance_verifications_business_profile_id_foreign` FOREIGN KEY (`business_profile_id`) REFERENCES `business_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `insurance_verifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `insurance_verifications`
--

LOCK TABLES `insurance_verifications` WRITE;
/*!40000 ALTER TABLE `insurance_verifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `insurance_verifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `integration_syncs`
--

DROP TABLE IF EXISTS `integration_syncs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `integration_syncs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `integration_id` bigint unsigned NOT NULL,
  `direction` enum('inbound','outbound') COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `records_processed` int NOT NULL DEFAULT '0',
  `records_created` int NOT NULL DEFAULT '0',
  `records_updated` int NOT NULL DEFAULT '0',
  `records_failed` int NOT NULL DEFAULT '0',
  `errors` json DEFAULT NULL,
  `status` enum('pending','running','completed','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `integration_syncs_integration_id_status_index` (`integration_id`,`status`),
  KEY `integration_syncs_integration_id_entity_type_index` (`integration_id`,`entity_type`),
  KEY `integration_syncs_status_created_at_index` (`status`,`created_at`),
  CONSTRAINT `integration_syncs_integration_id_foreign` FOREIGN KEY (`integration_id`) REFERENCES `integrations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `integration_syncs`
--

LOCK TABLES `integration_syncs` WRITE;
/*!40000 ALTER TABLE `integration_syncs` DISABLE KEYS */;
/*!40000 ALTER TABLE `integration_syncs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `integrations`
--

DROP TABLE IF EXISTS `integrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `integrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `provider` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('hr','scheduling','payroll','pos','calendar','accounting') COLLATE utf8mb4_unicode_ci NOT NULL,
  `credentials` json DEFAULT NULL,
  `settings` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `connected_at` timestamp NULL DEFAULT NULL,
  `last_sync_at` timestamp NULL DEFAULT NULL,
  `sync_errors` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `integrations_business_id_provider_unique` (`business_id`,`provider`),
  KEY `integrations_business_id_is_active_index` (`business_id`,`is_active`),
  KEY `integrations_provider_is_active_index` (`provider`,`is_active`),
  CONSTRAINT `integrations_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `integrations`
--

LOCK TABLES `integrations` WRITE;
/*!40000 ALTER TABLE `integrations` DISABLE KEYS */;
/*!40000 ALTER TABLE `integrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kyc_verifications`
--

DROP TABLE IF EXISTS `kyc_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kyc_verifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `status` enum('pending','in_review','approved','rejected','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `document_type` enum('passport','drivers_license','national_id','residence_permit') COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_country` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ISO 3166-1 alpha-2 country code',
  `document_expiry` date DEFAULT NULL,
  `document_front_path` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_back_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `selfie_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verification_result` json DEFAULT NULL COMMENT 'Provider response data',
  `confidence_score` decimal(5,4) DEFAULT NULL COMMENT '0.0000-1.0000 confidence',
  `provider` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual' COMMENT 'manual, onfido, jumio, veriff',
  `provider_reference` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'External provider reference ID',
  `provider_applicant_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Provider applicant/user ID',
  `provider_check_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Provider check/verification ID',
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `rejection_codes` json DEFAULT NULL COMMENT 'Structured rejection reasons',
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewer_notes` text COLLATE utf8mb4_unicode_ci,
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'When this verification expires',
  `attempt_count` tinyint unsigned NOT NULL DEFAULT '1',
  `max_attempts` tinyint unsigned NOT NULL DEFAULT '3',
  `last_attempt_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL COMMENT 'Additional verification data',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `kyc_verifications_reviewed_by_foreign` (`reviewed_by`),
  KEY `kyc_verifications_user_id_status_index` (`user_id`,`status`),
  KEY `kyc_verifications_status_created_at_index` (`status`,`created_at`),
  KEY `kyc_verifications_document_expiry_index` (`document_expiry`),
  KEY `kyc_verifications_expires_at_index` (`expires_at`),
  KEY `kyc_verifications_provider_reference_index` (`provider_reference`),
  KEY `kyc_verifications_status_index` (`status`),
  CONSTRAINT `kyc_verifications_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `kyc_verifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kyc_verifications`
--

LOCK TABLES `kyc_verifications` WRITE;
/*!40000 ALTER TABLE `kyc_verifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `kyc_verifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `labor_law_rules`
--

DROP TABLE IF EXISTS `labor_law_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `labor_law_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `jurisdiction` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rule_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `rule_type` enum('working_time','rest_period','break','overtime','age_restriction','wage','night_work') COLLATE utf8mb4_unicode_ci NOT NULL,
  `parameters` json NOT NULL,
  `enforcement` enum('hard_block','soft_warning','log_only') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'soft_warning',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `allows_opt_out` tinyint(1) NOT NULL DEFAULT '0',
  `opt_out_requirements` text COLLATE utf8mb4_unicode_ci,
  `legal_reference` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `effective_from` date DEFAULT NULL,
  `effective_until` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `labor_law_rules_rule_code_unique` (`rule_code`),
  KEY `labor_law_rules_jurisdiction_index` (`jurisdiction`),
  KEY `labor_law_rules_rule_type_index` (`rule_type`),
  KEY `labor_law_rules_is_active_index` (`is_active`),
  KEY `labor_law_rules_jurisdiction_rule_type_index` (`jurisdiction`,`rule_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `labor_law_rules`
--

LOCK TABLES `labor_law_rules` WRITE;
/*!40000 ALTER TABLE `labor_law_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `labor_law_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `liveness_checks`
--

DROP TABLE IF EXISTS `liveness_checks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `liveness_checks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `identity_verification_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `provider` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'onfido',
  `provider_check_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_report_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `check_type` enum('passive','active','video','motion') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `status` enum('pending','in_progress','processing','passed','failed','expired','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `challenges` json DEFAULT NULL,
  `challenge_responses` json DEFAULT NULL,
  `challenges_completed` int NOT NULL DEFAULT '0',
  `challenges_required` int NOT NULL DEFAULT '3',
  `result` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `liveness_score` decimal(5,4) DEFAULT NULL,
  `face_quality_score` decimal(5,4) DEFAULT NULL,
  `result_breakdown` json DEFAULT NULL,
  `face_match_attempted` tinyint(1) NOT NULL DEFAULT '0',
  `face_similarity_score` decimal(5,4) DEFAULT NULL,
  `face_match_result` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spoofing_checks` json DEFAULT NULL,
  `is_real_person` tinyint(1) DEFAULT NULL,
  `photo_detected` tinyint(1) NOT NULL DEFAULT '0',
  `screen_detected` tinyint(1) NOT NULL DEFAULT '0',
  `mask_detected` tinyint(1) NOT NULL DEFAULT '0',
  `deepfake_detected` tinyint(1) NOT NULL DEFAULT '0',
  `video_storage_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `frame_storage_paths` json DEFAULT NULL,
  `selfie_storage_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `storage_encryption_key` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `session_token` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `session_started_at` timestamp NULL DEFAULT NULL,
  `session_completed_at` timestamp NULL DEFAULT NULL,
  `session_duration_seconds` int DEFAULT NULL,
  `device_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_os` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `browser` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `camera_used` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `environment_checks` json DEFAULT NULL,
  `attempt_number` int NOT NULL DEFAULT '1',
  `failure_reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `failure_details` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `geolocation` json DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `liveness_checks_identity_verification_id_index` (`identity_verification_id`),
  KEY `liveness_checks_user_id_index` (`user_id`),
  KEY `liveness_checks_status_index` (`status`),
  KEY `liveness_checks_provider_check_id_index` (`provider_check_id`),
  KEY `liveness_checks_created_at_index` (`created_at`),
  CONSTRAINT `liveness_checks_identity_verification_id_foreign` FOREIGN KEY (`identity_verification_id`) REFERENCES `identity_verifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `liveness_checks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `liveness_checks`
--

LOCK TABLES `liveness_checks` WRITE;
/*!40000 ALTER TABLE `liveness_checks` DISABLE KEYS */;
/*!40000 ALTER TABLE `liveness_checks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `locales`
--

DROP TABLE IF EXISTS `locales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `locales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `native_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `flag_emoji` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_rtl` tinyint(1) NOT NULL DEFAULT '0',
  `date_format` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Y-m-d',
  `time_format` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'H:i',
  `datetime_format` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Y-m-d H:i',
  `number_decimal_separator` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '.',
  `number_thousands_separator` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ',',
  `currency_position` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'before',
  `translation_progress` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locales_code_unique` (`code`),
  KEY `locales_is_active_index` (`is_active`),
  KEY `locales_is_active_code_index` (`is_active`,`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `locales`
--

LOCK TABLES `locales` WRITE;
/*!40000 ALTER TABLE `locales` DISABLE KEYS */;
/*!40000 ALTER TABLE `locales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loyalty_points`
--

DROP TABLE IF EXISTS `loyalty_points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loyalty_points` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `points` int NOT NULL DEFAULT '0',
  `lifetime_points` int NOT NULL DEFAULT '0',
  `tier` enum('bronze','silver','gold','platinum') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bronze',
  `tier_expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loyalty_points_user_id_unique` (`user_id`),
  KEY `loyalty_points_tier_index` (`tier`),
  KEY `loyalty_points_points_index` (`points`),
  CONSTRAINT `loyalty_points_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loyalty_points`
--

LOCK TABLES `loyalty_points` WRITE;
/*!40000 ALTER TABLE `loyalty_points` DISABLE KEYS */;
/*!40000 ALTER TABLE `loyalty_points` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loyalty_redemptions`
--

DROP TABLE IF EXISTS `loyalty_redemptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loyalty_redemptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `loyalty_reward_id` bigint unsigned NOT NULL,
  `points_spent` int NOT NULL,
  `status` enum('pending','fulfilled','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `fulfilled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loyalty_redemptions_user_id_index` (`user_id`),
  KEY `loyalty_redemptions_loyalty_reward_id_index` (`loyalty_reward_id`),
  KEY `loyalty_redemptions_status_index` (`status`),
  CONSTRAINT `loyalty_redemptions_loyalty_reward_id_foreign` FOREIGN KEY (`loyalty_reward_id`) REFERENCES `loyalty_rewards` (`id`) ON DELETE CASCADE,
  CONSTRAINT `loyalty_redemptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loyalty_redemptions`
--

LOCK TABLES `loyalty_redemptions` WRITE;
/*!40000 ALTER TABLE `loyalty_redemptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `loyalty_redemptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loyalty_rewards`
--

DROP TABLE IF EXISTS `loyalty_rewards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loyalty_rewards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `points_required` int NOT NULL,
  `type` enum('cash_bonus','fee_discount','priority_matching','badge','merch') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reward_data` json DEFAULT NULL,
  `quantity_available` int DEFAULT NULL,
  `quantity_redeemed` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `min_tier` enum('bronze','silver','gold','platinum') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bronze',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loyalty_rewards_is_active_index` (`is_active`),
  KEY `loyalty_rewards_type_index` (`type`),
  KEY `loyalty_rewards_min_tier_index` (`min_tier`),
  KEY `loyalty_rewards_points_required_index` (`points_required`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loyalty_rewards`
--

LOCK TABLES `loyalty_rewards` WRITE;
/*!40000 ALTER TABLE `loyalty_rewards` DISABLE KEYS */;
/*!40000 ALTER TABLE `loyalty_rewards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loyalty_transactions`
--

DROP TABLE IF EXISTS `loyalty_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loyalty_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `type` enum('earned','redeemed','expired','bonus','adjustment') COLLATE utf8mb4_unicode_ci NOT NULL,
  `points` int NOT NULL,
  `balance_after` int NOT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loyalty_transactions_user_id_index` (`user_id`),
  KEY `loyalty_transactions_type_index` (`type`),
  KEY `loyalty_transactions_expires_at_index` (`expires_at`),
  KEY `loyalty_transactions_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  CONSTRAINT `loyalty_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loyalty_transactions`
--

LOCK TABLES `loyalty_transactions` WRITE;
/*!40000 ALTER TABLE `loyalty_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `loyalty_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `market_rates`
--

DROP TABLE IF EXISTS `market_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `market_rates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `country_code` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `state_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metro_area` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role_category` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `industry` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rate_low_cents` int NOT NULL,
  `rate_median_cents` int NOT NULL,
  `rate_high_cents` int NOT NULL,
  `rate_premium_cents` int DEFAULT NULL,
  `night_shift_multiplier` decimal(3,2) NOT NULL DEFAULT '1.15',
  `weekend_multiplier` decimal(3,2) NOT NULL DEFAULT '1.10',
  `holiday_multiplier` decimal(3,2) NOT NULL DEFAULT '1.50',
  `urgent_multiplier` decimal(3,2) NOT NULL DEFAULT '1.25',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `entry_level_adjustment_cents` int NOT NULL DEFAULT '0',
  `experienced_adjustment_cents` int NOT NULL DEFAULT '0',
  `data_source` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'platform',
  `sample_size` int NOT NULL DEFAULT '0',
  `data_collected_at` date DEFAULT NULL,
  `valid_from` date NOT NULL,
  `valid_until` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mr_location_idx` (`country_code`,`state_code`,`city`),
  KEY `mr_role_idx` (`role_category`,`role_name`),
  KEY `market_rates_industry_index` (`industry`),
  KEY `market_rates_is_active_valid_from_index` (`is_active`,`valid_from`)
) ENGINE=InnoDB AUTO_INCREMENT=371 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `market_rates`
--

LOCK TABLES `market_rates` WRITE;
/*!40000 ALTER TABLE `market_rates` DISABLE KEYS */;
INSERT INTO `market_rates` VALUES (1,'US','CA',NULL,NULL,'hospitality','Server',NULL,1440,1800,2400,2880,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',426,'2025-11-21','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(2,'US','CA',NULL,NULL,'hospitality','Bartender',NULL,1680,2160,3000,3600,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',260,'2025-12-04','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(3,'US','CA',NULL,NULL,'hospitality','Host/Hostess',NULL,1320,1680,2160,2592,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',75,'2025-11-29','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(4,'US','CA',NULL,NULL,'hospitality','Busser',NULL,1200,1560,1920,2304,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',474,'2025-11-27','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(5,'US','CA',NULL,NULL,'hospitality','Line Cook',NULL,1680,2040,2640,3168,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',323,'2025-12-12','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(6,'US','CA',NULL,NULL,'hospitality','Prep Cook',NULL,1440,1800,2280,2736,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',221,'2025-12-12','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(7,'US','CA',NULL,NULL,'hospitality','Dishwasher',NULL,1200,1560,1920,2304,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',351,'2025-12-04','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(8,'US','CA',NULL,NULL,'hospitality','Barista',NULL,1320,1680,2160,2592,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',445,'2025-12-16','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(9,'US','CA',NULL,NULL,'retail','Cashier',NULL,1320,1680,2040,2448,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',320,'2025-11-20','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(10,'US','CA',NULL,NULL,'retail','Sales Associate',NULL,1440,1800,2280,2736,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',255,'2025-12-02','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(11,'US','CA',NULL,NULL,'retail','Stock Associate',NULL,1320,1680,2040,2448,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',453,'2025-11-27','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(12,'US','CA',NULL,NULL,'retail','Visual Merchandiser',NULL,1680,2040,2520,3024,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',345,'2025-12-02','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(13,'US','CA',NULL,NULL,'retail','Customer Service Rep',NULL,1440,1800,2280,2736,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',166,'2025-11-26','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(14,'US','CA',NULL,NULL,'warehouse','Picker/Packer',NULL,1560,1920,2400,2880,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',117,'2025-11-29','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(15,'US','CA',NULL,NULL,'warehouse','Forklift Operator',NULL,1800,2280,2880,3456,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',118,'2025-11-25','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(16,'US','CA',NULL,NULL,'warehouse','Warehouse Associate',NULL,1440,1800,2280,2736,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',130,'2025-11-22','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(17,'US','CA',NULL,NULL,'warehouse','Loader/Unloader',NULL,1560,1920,2400,2880,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',184,'2025-12-06','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(18,'US','CA',NULL,NULL,'warehouse','Inventory Clerk',NULL,1560,1920,2400,2880,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',167,'2025-11-18','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(19,'US','CA',NULL,NULL,'events','Event Server',NULL,1680,2160,2880,3456,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',121,'2025-12-12','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(20,'US','CA',NULL,NULL,'events','Event Bartender',NULL,1920,2640,3600,4320,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',74,'2025-12-14','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(21,'US','CA',NULL,NULL,'events','Catering Staff',NULL,1560,2040,2640,3168,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',412,'2025-11-29','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(22,'US','CA',NULL,NULL,'events','Event Setup',NULL,1440,1800,2280,2736,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',297,'2025-11-24','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(23,'US','CA',NULL,NULL,'events','Brand Ambassador',NULL,1800,2400,3360,4032,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',320,'2025-12-11','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(24,'US','CA',NULL,NULL,'healthcare','CNA',NULL,1680,2160,2760,3312,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',338,'2025-11-19','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(25,'US','CA',NULL,NULL,'healthcare','Medical Assistant',NULL,1800,2280,2880,3456,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',341,'2025-12-03','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(26,'US','CA',NULL,NULL,'healthcare','Patient Transporter',NULL,1440,1800,2280,2736,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',191,'2025-11-28','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(27,'US','CA',NULL,NULL,'healthcare','Dietary Aide',NULL,1320,1680,2160,2592,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',90,'2025-11-25','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(28,'US','CA',NULL,NULL,'office','Receptionist',NULL,1560,1920,2400,2880,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',420,'2025-11-29','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(29,'US','CA',NULL,NULL,'office','Data Entry',NULL,1440,1800,2280,2736,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',158,'2025-12-02','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(30,'US','CA',NULL,NULL,'office','Administrative Assistant',NULL,1680,2160,2760,3312,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',318,'2025-12-01','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(31,'US','CA',NULL,NULL,'cleaning','Janitor',NULL,1320,1680,2160,2592,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',63,'2025-12-03','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(32,'US','CA',NULL,NULL,'cleaning','Housekeeper',NULL,1440,1800,2280,2736,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',181,'2025-11-21','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(33,'US','CA',NULL,NULL,'cleaning','Commercial Cleaner',NULL,1440,1800,2280,2736,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',152,'2025-11-25','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(34,'US','CA',NULL,NULL,'security','Security Guard',NULL,1680,2040,2640,3168,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',303,'2025-12-08','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(35,'US','CA',NULL,NULL,'security','Event Security',NULL,1800,2280,3000,3600,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',147,'2025-12-13','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(36,'US','CA',NULL,NULL,'delivery','Delivery Driver',NULL,1680,2160,2760,3312,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',131,'2025-11-24','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(37,'US','CA',NULL,NULL,'delivery','Courier',NULL,1560,1920,2520,3024,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',419,'2025-12-01','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(38,'US','NY',NULL,NULL,'hospitality','Server',NULL,1380,1724,2300,2760,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',189,'2025-12-17','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(39,'US','NY',NULL,NULL,'hospitality','Bartender',NULL,1609,2070,2875,3450,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',426,'2025-11-24','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(40,'US','NY',NULL,NULL,'hospitality','Host/Hostess',NULL,1265,1609,2070,2484,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',354,'2025-11-20','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(41,'US','NY',NULL,NULL,'hospitality','Busser',NULL,1150,1494,1839,2207,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',395,'2025-12-13','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(42,'US','NY',NULL,NULL,'hospitality','Line Cook',NULL,1609,1954,2530,3036,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',188,'2025-12-10','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(43,'US','NY',NULL,NULL,'hospitality','Prep Cook',NULL,1380,1724,2185,2622,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',225,'2025-12-01','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(44,'US','NY',NULL,NULL,'hospitality','Dishwasher',NULL,1150,1494,1839,2207,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',211,'2025-11-30','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(45,'US','NY',NULL,NULL,'hospitality','Barista',NULL,1265,1609,2070,2484,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',416,'2025-11-25','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(46,'US','NY',NULL,NULL,'retail','Cashier',NULL,1265,1609,1954,2345,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',98,'2025-12-12','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(47,'US','NY',NULL,NULL,'retail','Sales Associate',NULL,1380,1724,2185,2622,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',493,'2025-12-04','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(48,'US','NY',NULL,NULL,'retail','Stock Associate',NULL,1265,1609,1954,2345,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',442,'2025-11-21','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(49,'US','NY',NULL,NULL,'retail','Visual Merchandiser',NULL,1609,1954,2415,2898,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',221,'2025-11-21','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(50,'US','NY',NULL,NULL,'retail','Customer Service Rep',NULL,1380,1724,2185,2622,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',473,'2025-11-24','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(51,'US','NY',NULL,NULL,'warehouse','Picker/Packer',NULL,1494,1839,2300,2760,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',301,'2025-11-22','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(52,'US','NY',NULL,NULL,'warehouse','Forklift Operator',NULL,1724,2185,2760,3312,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',122,'2025-12-05','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(53,'US','NY',NULL,NULL,'warehouse','Warehouse Associate',NULL,1380,1724,2185,2622,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',119,'2025-12-05','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(54,'US','NY',NULL,NULL,'warehouse','Loader/Unloader',NULL,1494,1839,2300,2760,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',225,'2025-11-27','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(55,'US','NY',NULL,NULL,'warehouse','Inventory Clerk',NULL,1494,1839,2300,2760,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',289,'2025-12-07','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(56,'US','NY',NULL,NULL,'events','Event Server',NULL,1609,2070,2760,3312,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',453,'2025-12-13','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(57,'US','NY',NULL,NULL,'events','Event Bartender',NULL,1839,2530,3449,4139,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',132,'2025-12-10','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(58,'US','NY',NULL,NULL,'events','Catering Staff',NULL,1494,1954,2530,3036,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',428,'2025-11-29','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(59,'US','NY',NULL,NULL,'events','Event Setup',NULL,1380,1724,2185,2622,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',448,'2025-12-03','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(60,'US','NY',NULL,NULL,'events','Brand Ambassador',NULL,1724,2300,3219,3863,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',340,'2025-11-18','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(61,'US','NY',NULL,NULL,'healthcare','CNA',NULL,1609,2070,2645,3174,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',97,'2025-12-14','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(62,'US','NY',NULL,NULL,'healthcare','Medical Assistant',NULL,1724,2185,2760,3312,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',89,'2025-11-19','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(63,'US','NY',NULL,NULL,'healthcare','Patient Transporter',NULL,1380,1724,2185,2622,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',312,'2025-11-24','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(64,'US','NY',NULL,NULL,'healthcare','Dietary Aide',NULL,1265,1609,2070,2484,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',136,'2025-11-19','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(65,'US','NY',NULL,NULL,'office','Receptionist',NULL,1494,1839,2300,2760,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',59,'2025-12-03','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(66,'US','NY',NULL,NULL,'office','Data Entry',NULL,1380,1724,2185,2622,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',177,'2025-12-01','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(67,'US','NY',NULL,NULL,'office','Administrative Assistant',NULL,1609,2070,2645,3174,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',320,'2025-12-01','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(68,'US','NY',NULL,NULL,'cleaning','Janitor',NULL,1265,1609,2070,2484,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',127,'2025-11-24','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(69,'US','NY',NULL,NULL,'cleaning','Housekeeper',NULL,1380,1724,2185,2622,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',61,'2025-11-29','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(70,'US','NY',NULL,NULL,'cleaning','Commercial Cleaner',NULL,1380,1724,2185,2622,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',141,'2025-12-03','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(71,'US','NY',NULL,NULL,'security','Security Guard',NULL,1609,1954,2530,3036,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',316,'2025-12-09','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(72,'US','NY',NULL,NULL,'security','Event Security',NULL,1724,2185,2875,3450,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',205,'2025-11-20','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(73,'US','NY',NULL,NULL,'delivery','Delivery Driver',NULL,1609,2070,2645,3174,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',492,'2025-12-15','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(74,'US','NY',NULL,NULL,'delivery','Courier',NULL,1494,1839,2415,2898,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',220,'2025-12-07','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(75,'US','WA',NULL,NULL,'hospitality','Server',NULL,1320,1650,2200,2640,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',319,'2025-12-09','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(76,'US','WA',NULL,NULL,'hospitality','Bartender',NULL,1540,1980,2750,3300,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',494,'2025-11-24','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(77,'US','WA',NULL,NULL,'hospitality','Host/Hostess',NULL,1210,1540,1980,2376,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',296,'2025-11-28','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(78,'US','WA',NULL,NULL,'hospitality','Busser',NULL,1100,1430,1760,2112,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',286,'2025-12-05','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(79,'US','WA',NULL,NULL,'hospitality','Line Cook',NULL,1540,1870,2420,2904,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',53,'2025-12-04','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(80,'US','WA',NULL,NULL,'hospitality','Prep Cook',NULL,1320,1650,2090,2508,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',156,'2025-11-27','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(81,'US','WA',NULL,NULL,'hospitality','Dishwasher',NULL,1100,1430,1760,2112,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',349,'2025-11-18','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(82,'US','WA',NULL,NULL,'hospitality','Barista',NULL,1210,1540,1980,2376,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',331,'2025-12-10','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(83,'US','WA',NULL,NULL,'retail','Cashier',NULL,1210,1540,1870,2244,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',387,'2025-11-18','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(84,'US','WA',NULL,NULL,'retail','Sales Associate',NULL,1320,1650,2090,2508,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',287,'2025-12-04','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(85,'US','WA',NULL,NULL,'retail','Stock Associate',NULL,1210,1540,1870,2244,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',279,'2025-12-12','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(86,'US','WA',NULL,NULL,'retail','Visual Merchandiser',NULL,1540,1870,2310,2772,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',250,'2025-12-15','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(87,'US','WA',NULL,NULL,'retail','Customer Service Rep',NULL,1320,1650,2090,2508,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',190,'2025-11-21','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(88,'US','WA',NULL,NULL,'warehouse','Picker/Packer',NULL,1430,1760,2200,2640,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',205,'2025-11-29','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(89,'US','WA',NULL,NULL,'warehouse','Forklift Operator',NULL,1650,2090,2640,3168,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',196,'2025-11-23','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(90,'US','WA',NULL,NULL,'warehouse','Warehouse Associate',NULL,1320,1650,2090,2508,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',91,'2025-12-17','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(91,'US','WA',NULL,NULL,'warehouse','Loader/Unloader',NULL,1430,1760,2200,2640,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',426,'2025-12-05','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(92,'US','WA',NULL,NULL,'warehouse','Inventory Clerk',NULL,1430,1760,2200,2640,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',484,'2025-12-04','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(93,'US','WA',NULL,NULL,'events','Event Server',NULL,1540,1980,2640,3168,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',150,'2025-12-07','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(94,'US','WA',NULL,NULL,'events','Event Bartender',NULL,1760,2420,3300,3960,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',322,'2025-12-07','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(95,'US','WA',NULL,NULL,'events','Catering Staff',NULL,1430,1870,2420,2904,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',98,'2025-12-15','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(96,'US','WA',NULL,NULL,'events','Event Setup',NULL,1320,1650,2090,2508,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',320,'2025-12-09','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(97,'US','WA',NULL,NULL,'events','Brand Ambassador',NULL,1650,2200,3080,3696,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',85,'2025-11-24','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(98,'US','WA',NULL,NULL,'healthcare','CNA',NULL,1540,1980,2530,3036,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',66,'2025-11-26','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(99,'US','WA',NULL,NULL,'healthcare','Medical Assistant',NULL,1650,2090,2640,3168,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',358,'2025-12-04','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(100,'US','WA',NULL,NULL,'healthcare','Patient Transporter',NULL,1320,1650,2090,2508,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',316,'2025-11-21','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(101,'US','WA',NULL,NULL,'healthcare','Dietary Aide',NULL,1210,1540,1980,2376,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',233,'2025-11-18','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(102,'US','WA',NULL,NULL,'office','Receptionist',NULL,1430,1760,2200,2640,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',71,'2025-12-14','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(103,'US','WA',NULL,NULL,'office','Data Entry',NULL,1320,1650,2090,2508,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',386,'2025-12-01','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(104,'US','WA',NULL,NULL,'office','Administrative Assistant',NULL,1540,1980,2530,3036,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',218,'2025-11-28','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(105,'US','WA',NULL,NULL,'cleaning','Janitor',NULL,1210,1540,1980,2376,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',346,'2025-12-03','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(106,'US','WA',NULL,NULL,'cleaning','Housekeeper',NULL,1320,1650,2090,2508,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',211,'2025-11-19','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(107,'US','WA',NULL,NULL,'cleaning','Commercial Cleaner',NULL,1320,1650,2090,2508,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',128,'2025-11-18','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(108,'US','WA',NULL,NULL,'security','Security Guard',NULL,1540,1870,2420,2904,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',414,'2025-11-18','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(109,'US','WA',NULL,NULL,'security','Event Security',NULL,1650,2090,2750,3300,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',168,'2025-12-16','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(110,'US','WA',NULL,NULL,'delivery','Delivery Driver',NULL,1540,1980,2530,3036,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',248,'2025-11-29','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(111,'US','WA',NULL,NULL,'delivery','Courier',NULL,1430,1760,2310,2772,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',227,'2025-12-13','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(112,'US','TX',NULL,NULL,'hospitality','Server',NULL,1140,1425,1900,2280,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',299,'2025-12-11','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(113,'US','TX',NULL,NULL,'hospitality','Bartender',NULL,1330,1710,2375,2850,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',468,'2025-12-05','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(114,'US','TX',NULL,NULL,'hospitality','Host/Hostess',NULL,1045,1330,1710,2052,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',272,'2025-12-05','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(115,'US','TX',NULL,NULL,'hospitality','Busser',NULL,950,1235,1520,1824,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',338,'2025-11-24','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(116,'US','TX',NULL,NULL,'hospitality','Line Cook',NULL,1330,1615,2090,2508,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',80,'2025-12-11','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(117,'US','TX',NULL,NULL,'hospitality','Prep Cook',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',168,'2025-11-30','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(118,'US','TX',NULL,NULL,'hospitality','Dishwasher',NULL,950,1235,1520,1824,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',246,'2025-12-02','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(119,'US','TX',NULL,NULL,'hospitality','Barista',NULL,1045,1330,1710,2052,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',445,'2025-11-20','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(120,'US','TX',NULL,NULL,'retail','Cashier',NULL,1045,1330,1615,1938,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',239,'2025-12-08','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(121,'US','TX',NULL,NULL,'retail','Sales Associate',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',184,'2025-12-05','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(122,'US','TX',NULL,NULL,'retail','Stock Associate',NULL,1045,1330,1615,1938,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',125,'2025-11-30','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(123,'US','TX',NULL,NULL,'retail','Visual Merchandiser',NULL,1330,1615,1995,2394,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',91,'2025-11-18','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(124,'US','TX',NULL,NULL,'retail','Customer Service Rep',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',234,'2025-12-04','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(125,'US','TX',NULL,NULL,'warehouse','Picker/Packer',NULL,1235,1520,1900,2280,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',379,'2025-12-11','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(126,'US','TX',NULL,NULL,'warehouse','Forklift Operator',NULL,1425,1805,2280,2736,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',313,'2025-12-06','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(127,'US','TX',NULL,NULL,'warehouse','Warehouse Associate',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',115,'2025-12-02','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(128,'US','TX',NULL,NULL,'warehouse','Loader/Unloader',NULL,1235,1520,1900,2280,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',228,'2025-11-29','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(129,'US','TX',NULL,NULL,'warehouse','Inventory Clerk',NULL,1235,1520,1900,2280,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',466,'2025-11-26','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(130,'US','TX',NULL,NULL,'events','Event Server',NULL,1330,1710,2280,2736,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',234,'2025-12-08','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(131,'US','TX',NULL,NULL,'events','Event Bartender',NULL,1520,2090,2850,3420,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',427,'2025-11-22','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(132,'US','TX',NULL,NULL,'events','Catering Staff',NULL,1235,1615,2090,2508,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',77,'2025-12-12','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(133,'US','TX',NULL,NULL,'events','Event Setup',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',479,'2025-12-07','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(134,'US','TX',NULL,NULL,'events','Brand Ambassador',NULL,1425,1900,2660,3192,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',488,'2025-11-23','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(135,'US','TX',NULL,NULL,'healthcare','CNA',NULL,1330,1710,2185,2622,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',329,'2025-12-13','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(136,'US','TX',NULL,NULL,'healthcare','Medical Assistant',NULL,1425,1805,2280,2736,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',446,'2025-11-30','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(137,'US','TX',NULL,NULL,'healthcare','Patient Transporter',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',444,'2025-11-25','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(138,'US','TX',NULL,NULL,'healthcare','Dietary Aide',NULL,1045,1330,1710,2052,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',407,'2025-12-12','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(139,'US','TX',NULL,NULL,'office','Receptionist',NULL,1235,1520,1900,2280,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',145,'2025-12-11','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(140,'US','TX',NULL,NULL,'office','Data Entry',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',180,'2025-12-06','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(141,'US','TX',NULL,NULL,'office','Administrative Assistant',NULL,1330,1710,2185,2622,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',110,'2025-12-08','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(142,'US','TX',NULL,NULL,'cleaning','Janitor',NULL,1045,1330,1710,2052,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',444,'2025-12-10','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(143,'US','TX',NULL,NULL,'cleaning','Housekeeper',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',192,'2025-11-21','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(144,'US','TX',NULL,NULL,'cleaning','Commercial Cleaner',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',298,'2025-12-10','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(145,'US','TX',NULL,NULL,'security','Security Guard',NULL,1330,1615,2090,2508,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',361,'2025-11-23','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(146,'US','TX',NULL,NULL,'security','Event Security',NULL,1425,1805,2375,2850,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',78,'2025-12-12','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(147,'US','TX',NULL,NULL,'delivery','Delivery Driver',NULL,1330,1710,2185,2622,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',411,'2025-12-01','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(148,'US','TX',NULL,NULL,'delivery','Courier',NULL,1235,1520,1995,2394,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',144,'2025-11-30','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(149,'US','FL',NULL,NULL,'hospitality','Server',NULL,1176,1470,1960,2352,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',382,'2025-12-12','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(150,'US','FL',NULL,NULL,'hospitality','Bartender',NULL,1372,1764,2450,2940,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',270,'2025-12-10','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(151,'US','FL',NULL,NULL,'hospitality','Host/Hostess',NULL,1078,1372,1764,2116,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',464,'2025-12-05','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(152,'US','FL',NULL,NULL,'hospitality','Busser',NULL,980,1274,1568,1881,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',57,'2025-12-11','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(153,'US','FL',NULL,NULL,'hospitality','Line Cook',NULL,1372,1666,2156,2587,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',312,'2025-11-23','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(154,'US','FL',NULL,NULL,'hospitality','Prep Cook',NULL,1176,1470,1862,2234,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',325,'2025-12-13','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(155,'US','FL',NULL,NULL,'hospitality','Dishwasher',NULL,980,1274,1568,1881,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',209,'2025-11-24','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(156,'US','FL',NULL,NULL,'hospitality','Barista',NULL,1078,1372,1764,2116,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',311,'2025-11-21','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(157,'US','FL',NULL,NULL,'retail','Cashier',NULL,1078,1372,1666,1999,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',141,'2025-11-29','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(158,'US','FL',NULL,NULL,'retail','Sales Associate',NULL,1176,1470,1862,2234,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',210,'2025-12-05','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(159,'US','FL',NULL,NULL,'retail','Stock Associate',NULL,1078,1372,1666,1999,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',78,'2025-12-08','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(160,'US','FL',NULL,NULL,'retail','Visual Merchandiser',NULL,1372,1666,2058,2469,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',323,'2025-12-06','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(161,'US','FL',NULL,NULL,'retail','Customer Service Rep',NULL,1176,1470,1862,2234,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',378,'2025-11-20','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(162,'US','FL',NULL,NULL,'warehouse','Picker/Packer',NULL,1274,1568,1960,2352,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',343,'2025-11-23','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(163,'US','FL',NULL,NULL,'warehouse','Forklift Operator',NULL,1470,1862,2352,2822,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',210,'2025-11-19','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(164,'US','FL',NULL,NULL,'warehouse','Warehouse Associate',NULL,1176,1470,1862,2234,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',325,'2025-11-22','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(165,'US','FL',NULL,NULL,'warehouse','Loader/Unloader',NULL,1274,1568,1960,2352,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',381,'2025-11-28','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(166,'US','FL',NULL,NULL,'warehouse','Inventory Clerk',NULL,1274,1568,1960,2352,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',87,'2025-11-22','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(167,'US','FL',NULL,NULL,'events','Event Server',NULL,1372,1764,2352,2822,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',214,'2025-12-15','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(168,'US','FL',NULL,NULL,'events','Event Bartender',NULL,1568,2156,2940,3528,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',89,'2025-12-06','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(169,'US','FL',NULL,NULL,'events','Catering Staff',NULL,1274,1666,2156,2587,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',178,'2025-12-07','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(170,'US','FL',NULL,NULL,'events','Event Setup',NULL,1176,1470,1862,2234,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',362,'2025-12-06','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(171,'US','FL',NULL,NULL,'events','Brand Ambassador',NULL,1470,1960,2744,3292,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',480,'2025-12-17','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(172,'US','FL',NULL,NULL,'healthcare','CNA',NULL,1372,1764,2254,2704,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',139,'2025-12-14','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(173,'US','FL',NULL,NULL,'healthcare','Medical Assistant',NULL,1470,1862,2352,2822,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',126,'2025-12-02','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(174,'US','FL',NULL,NULL,'healthcare','Patient Transporter',NULL,1176,1470,1862,2234,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',387,'2025-12-06','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(175,'US','FL',NULL,NULL,'healthcare','Dietary Aide',NULL,1078,1372,1764,2116,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',114,'2025-11-22','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(176,'US','FL',NULL,NULL,'office','Receptionist',NULL,1274,1568,1960,2352,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',466,'2025-11-23','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(177,'US','FL',NULL,NULL,'office','Data Entry',NULL,1176,1470,1862,2234,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',320,'2025-11-28','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(178,'US','FL',NULL,NULL,'office','Administrative Assistant',NULL,1372,1764,2254,2704,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',289,'2025-12-01','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(179,'US','FL',NULL,NULL,'cleaning','Janitor',NULL,1078,1372,1764,2116,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',114,'2025-12-16','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(180,'US','FL',NULL,NULL,'cleaning','Housekeeper',NULL,1176,1470,1862,2234,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',203,'2025-12-05','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(181,'US','FL',NULL,NULL,'cleaning','Commercial Cleaner',NULL,1176,1470,1862,2234,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',244,'2025-12-05','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(182,'US','FL',NULL,NULL,'security','Security Guard',NULL,1372,1666,2156,2587,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',471,'2025-11-19','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(183,'US','FL',NULL,NULL,'security','Event Security',NULL,1470,1862,2450,2940,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',56,'2025-12-17','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(184,'US','FL',NULL,NULL,'delivery','Delivery Driver',NULL,1372,1764,2254,2704,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',125,'2025-12-02','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(185,'US','FL',NULL,NULL,'delivery','Courier',NULL,1274,1568,2058,2469,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',398,'2025-12-01','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(186,'US','IL',NULL,NULL,'hospitality','Server',NULL,1260,1575,2100,2520,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',55,'2025-11-27','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(187,'US','IL',NULL,NULL,'hospitality','Bartender',NULL,1470,1890,2625,3150,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',215,'2025-11-25','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(188,'US','IL',NULL,NULL,'hospitality','Host/Hostess',NULL,1155,1470,1890,2268,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',248,'2025-11-28','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(189,'US','IL',NULL,NULL,'hospitality','Busser',NULL,1050,1365,1680,2016,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',215,'2025-11-28','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(190,'US','IL',NULL,NULL,'hospitality','Line Cook',NULL,1470,1785,2310,2772,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',281,'2025-11-20','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(191,'US','IL',NULL,NULL,'hospitality','Prep Cook',NULL,1260,1575,1995,2394,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',442,'2025-11-23','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(192,'US','IL',NULL,NULL,'hospitality','Dishwasher',NULL,1050,1365,1680,2016,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',53,'2025-12-07','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(193,'US','IL',NULL,NULL,'hospitality','Barista',NULL,1155,1470,1890,2268,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',142,'2025-12-13','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(194,'US','IL',NULL,NULL,'retail','Cashier',NULL,1155,1470,1785,2142,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',71,'2025-12-05','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(195,'US','IL',NULL,NULL,'retail','Sales Associate',NULL,1260,1575,1995,2394,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',136,'2025-12-09','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(196,'US','IL',NULL,NULL,'retail','Stock Associate',NULL,1155,1470,1785,2142,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',79,'2025-12-10','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(197,'US','IL',NULL,NULL,'retail','Visual Merchandiser',NULL,1470,1785,2205,2646,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',472,'2025-12-12','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(198,'US','IL',NULL,NULL,'retail','Customer Service Rep',NULL,1260,1575,1995,2394,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',114,'2025-12-04','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(199,'US','IL',NULL,NULL,'warehouse','Picker/Packer',NULL,1365,1680,2100,2520,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',273,'2025-12-02','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(200,'US','IL',NULL,NULL,'warehouse','Forklift Operator',NULL,1575,1995,2520,3024,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',391,'2025-11-23','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(201,'US','IL',NULL,NULL,'warehouse','Warehouse Associate',NULL,1260,1575,1995,2394,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',220,'2025-12-09','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(202,'US','IL',NULL,NULL,'warehouse','Loader/Unloader',NULL,1365,1680,2100,2520,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',471,'2025-11-23','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(203,'US','IL',NULL,NULL,'warehouse','Inventory Clerk',NULL,1365,1680,2100,2520,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',226,'2025-11-24','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(204,'US','IL',NULL,NULL,'events','Event Server',NULL,1470,1890,2520,3024,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',492,'2025-12-03','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(205,'US','IL',NULL,NULL,'events','Event Bartender',NULL,1680,2310,3150,3780,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',80,'2025-12-01','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(206,'US','IL',NULL,NULL,'events','Catering Staff',NULL,1365,1785,2310,2772,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',73,'2025-11-25','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(207,'US','IL',NULL,NULL,'events','Event Setup',NULL,1260,1575,1995,2394,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',63,'2025-12-09','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(208,'US','IL',NULL,NULL,'events','Brand Ambassador',NULL,1575,2100,2940,3528,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',479,'2025-12-11','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(209,'US','IL',NULL,NULL,'healthcare','CNA',NULL,1470,1890,2415,2898,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',146,'2025-11-30','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(210,'US','IL',NULL,NULL,'healthcare','Medical Assistant',NULL,1575,1995,2520,3024,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',227,'2025-12-15','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(211,'US','IL',NULL,NULL,'healthcare','Patient Transporter',NULL,1260,1575,1995,2394,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',270,'2025-11-24','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(212,'US','IL',NULL,NULL,'healthcare','Dietary Aide',NULL,1155,1470,1890,2268,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',410,'2025-12-12','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(213,'US','IL',NULL,NULL,'office','Receptionist',NULL,1365,1680,2100,2520,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',390,'2025-12-04','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(214,'US','IL',NULL,NULL,'office','Data Entry',NULL,1260,1575,1995,2394,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',398,'2025-12-06','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(215,'US','IL',NULL,NULL,'office','Administrative Assistant',NULL,1470,1890,2415,2898,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',246,'2025-12-10','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(216,'US','IL',NULL,NULL,'cleaning','Janitor',NULL,1155,1470,1890,2268,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',321,'2025-12-05','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(217,'US','IL',NULL,NULL,'cleaning','Housekeeper',NULL,1260,1575,1995,2394,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',364,'2025-11-30','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(218,'US','IL',NULL,NULL,'cleaning','Commercial Cleaner',NULL,1260,1575,1995,2394,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',149,'2025-12-15','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(219,'US','IL',NULL,NULL,'security','Security Guard',NULL,1470,1785,2310,2772,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',95,'2025-12-11','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(220,'US','IL',NULL,NULL,'security','Event Security',NULL,1575,1995,2625,3150,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',249,'2025-11-21','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(221,'US','IL',NULL,NULL,'delivery','Delivery Driver',NULL,1470,1890,2415,2898,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',72,'2025-11-28','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(222,'US','IL',NULL,NULL,'delivery','Courier',NULL,1365,1680,2205,2646,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',66,'2025-11-22','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(223,'US','PA',NULL,NULL,'hospitality','Server',NULL,1200,1500,2000,2400,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',309,'2025-12-08','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(224,'US','PA',NULL,NULL,'hospitality','Bartender',NULL,1400,1800,2500,3000,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',60,'2025-11-28','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(225,'US','PA',NULL,NULL,'hospitality','Host/Hostess',NULL,1100,1400,1800,2160,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',278,'2025-12-15','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(226,'US','PA',NULL,NULL,'hospitality','Busser',NULL,1000,1300,1600,1920,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',205,'2025-12-02','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(227,'US','PA',NULL,NULL,'hospitality','Line Cook',NULL,1400,1700,2200,2640,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',296,'2025-12-13','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(228,'US','PA',NULL,NULL,'hospitality','Prep Cook',NULL,1200,1500,1900,2280,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',475,'2025-12-14','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(229,'US','PA',NULL,NULL,'hospitality','Dishwasher',NULL,1000,1300,1600,1920,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',159,'2025-12-14','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(230,'US','PA',NULL,NULL,'hospitality','Barista',NULL,1100,1400,1800,2160,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',84,'2025-11-30','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(231,'US','PA',NULL,NULL,'retail','Cashier',NULL,1100,1400,1700,2040,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',297,'2025-12-12','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(232,'US','PA',NULL,NULL,'retail','Sales Associate',NULL,1200,1500,1900,2280,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',58,'2025-12-02','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(233,'US','PA',NULL,NULL,'retail','Stock Associate',NULL,1100,1400,1700,2040,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',384,'2025-12-03','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(234,'US','PA',NULL,NULL,'retail','Visual Merchandiser',NULL,1400,1700,2100,2520,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',207,'2025-11-29','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(235,'US','PA',NULL,NULL,'retail','Customer Service Rep',NULL,1200,1500,1900,2280,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',52,'2025-11-28','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(236,'US','PA',NULL,NULL,'warehouse','Picker/Packer',NULL,1300,1600,2000,2400,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',417,'2025-12-01','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(237,'US','PA',NULL,NULL,'warehouse','Forklift Operator',NULL,1500,1900,2400,2880,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',244,'2025-11-22','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(238,'US','PA',NULL,NULL,'warehouse','Warehouse Associate',NULL,1200,1500,1900,2280,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',181,'2025-12-03','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(239,'US','PA',NULL,NULL,'warehouse','Loader/Unloader',NULL,1300,1600,2000,2400,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',370,'2025-12-02','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(240,'US','PA',NULL,NULL,'warehouse','Inventory Clerk',NULL,1300,1600,2000,2400,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',453,'2025-12-10','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(241,'US','PA',NULL,NULL,'events','Event Server',NULL,1400,1800,2400,2880,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',417,'2025-12-14','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(242,'US','PA',NULL,NULL,'events','Event Bartender',NULL,1600,2200,3000,3600,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',456,'2025-12-13','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(243,'US','PA',NULL,NULL,'events','Catering Staff',NULL,1300,1700,2200,2640,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',196,'2025-11-22','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(244,'US','PA',NULL,NULL,'events','Event Setup',NULL,1200,1500,1900,2280,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',328,'2025-12-06','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(245,'US','PA',NULL,NULL,'events','Brand Ambassador',NULL,1500,2000,2800,3360,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',89,'2025-12-08','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(246,'US','PA',NULL,NULL,'healthcare','CNA',NULL,1400,1800,2300,2760,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',489,'2025-12-09','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(247,'US','PA',NULL,NULL,'healthcare','Medical Assistant',NULL,1500,1900,2400,2880,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',97,'2025-12-17','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(248,'US','PA',NULL,NULL,'healthcare','Patient Transporter',NULL,1200,1500,1900,2280,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',272,'2025-11-28','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(249,'US','PA',NULL,NULL,'healthcare','Dietary Aide',NULL,1100,1400,1800,2160,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',491,'2025-12-15','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(250,'US','PA',NULL,NULL,'office','Receptionist',NULL,1300,1600,2000,2400,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',113,'2025-12-09','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(251,'US','PA',NULL,NULL,'office','Data Entry',NULL,1200,1500,1900,2280,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',432,'2025-12-09','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(252,'US','PA',NULL,NULL,'office','Administrative Assistant',NULL,1400,1800,2300,2760,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',475,'2025-11-26','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(253,'US','PA',NULL,NULL,'cleaning','Janitor',NULL,1100,1400,1800,2160,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',208,'2025-11-20','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(254,'US','PA',NULL,NULL,'cleaning','Housekeeper',NULL,1200,1500,1900,2280,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',440,'2025-11-20','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(255,'US','PA',NULL,NULL,'cleaning','Commercial Cleaner',NULL,1200,1500,1900,2280,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',446,'2025-11-26','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(256,'US','PA',NULL,NULL,'security','Security Guard',NULL,1400,1700,2200,2640,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',413,'2025-12-08','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(257,'US','PA',NULL,NULL,'security','Event Security',NULL,1500,1900,2500,3000,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',358,'2025-12-07','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(258,'US','PA',NULL,NULL,'delivery','Delivery Driver',NULL,1400,1800,2300,2760,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',254,'2025-12-12','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(259,'US','PA',NULL,NULL,'delivery','Courier',NULL,1300,1600,2100,2520,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',237,'2025-11-20','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(260,'US','OH',NULL,NULL,'hospitality','Server',NULL,1140,1425,1900,2280,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',207,'2025-12-09','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(261,'US','OH',NULL,NULL,'hospitality','Bartender',NULL,1330,1710,2375,2850,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',172,'2025-12-12','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(262,'US','OH',NULL,NULL,'hospitality','Host/Hostess',NULL,1045,1330,1710,2052,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',144,'2025-12-06','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(263,'US','OH',NULL,NULL,'hospitality','Busser',NULL,950,1235,1520,1824,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',324,'2025-12-17','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(264,'US','OH',NULL,NULL,'hospitality','Line Cook',NULL,1330,1615,2090,2508,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',365,'2025-12-16','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(265,'US','OH',NULL,NULL,'hospitality','Prep Cook',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',217,'2025-11-26','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(266,'US','OH',NULL,NULL,'hospitality','Dishwasher',NULL,950,1235,1520,1824,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',362,'2025-11-22','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(267,'US','OH',NULL,NULL,'hospitality','Barista',NULL,1045,1330,1710,2052,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',109,'2025-12-01','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(268,'US','OH',NULL,NULL,'retail','Cashier',NULL,1045,1330,1615,1938,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',165,'2025-12-05','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(269,'US','OH',NULL,NULL,'retail','Sales Associate',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',228,'2025-12-14','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(270,'US','OH',NULL,NULL,'retail','Stock Associate',NULL,1045,1330,1615,1938,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',261,'2025-12-05','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(271,'US','OH',NULL,NULL,'retail','Visual Merchandiser',NULL,1330,1615,1995,2394,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',77,'2025-12-11','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(272,'US','OH',NULL,NULL,'retail','Customer Service Rep',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',390,'2025-12-07','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(273,'US','OH',NULL,NULL,'warehouse','Picker/Packer',NULL,1235,1520,1900,2280,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',113,'2025-11-26','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(274,'US','OH',NULL,NULL,'warehouse','Forklift Operator',NULL,1425,1805,2280,2736,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',426,'2025-12-07','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(275,'US','OH',NULL,NULL,'warehouse','Warehouse Associate',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',391,'2025-11-24','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(276,'US','OH',NULL,NULL,'warehouse','Loader/Unloader',NULL,1235,1520,1900,2280,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',218,'2025-11-20','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(277,'US','OH',NULL,NULL,'warehouse','Inventory Clerk',NULL,1235,1520,1900,2280,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',161,'2025-12-13','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(278,'US','OH',NULL,NULL,'events','Event Server',NULL,1330,1710,2280,2736,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',245,'2025-11-28','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(279,'US','OH',NULL,NULL,'events','Event Bartender',NULL,1520,2090,2850,3420,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',472,'2025-12-12','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(280,'US','OH',NULL,NULL,'events','Catering Staff',NULL,1235,1615,2090,2508,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',453,'2025-12-17','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(281,'US','OH',NULL,NULL,'events','Event Setup',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',201,'2025-12-13','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(282,'US','OH',NULL,NULL,'events','Brand Ambassador',NULL,1425,1900,2660,3192,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',389,'2025-12-17','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(283,'US','OH',NULL,NULL,'healthcare','CNA',NULL,1330,1710,2185,2622,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',198,'2025-12-03','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(284,'US','OH',NULL,NULL,'healthcare','Medical Assistant',NULL,1425,1805,2280,2736,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',434,'2025-12-08','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(285,'US','OH',NULL,NULL,'healthcare','Patient Transporter',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',475,'2025-11-21','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(286,'US','OH',NULL,NULL,'healthcare','Dietary Aide',NULL,1045,1330,1710,2052,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',405,'2025-12-14','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(287,'US','OH',NULL,NULL,'office','Receptionist',NULL,1235,1520,1900,2280,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',340,'2025-12-09','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(288,'US','OH',NULL,NULL,'office','Data Entry',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',389,'2025-11-23','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(289,'US','OH',NULL,NULL,'office','Administrative Assistant',NULL,1330,1710,2185,2622,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',279,'2025-11-29','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(290,'US','OH',NULL,NULL,'cleaning','Janitor',NULL,1045,1330,1710,2052,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',116,'2025-12-10','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(291,'US','OH',NULL,NULL,'cleaning','Housekeeper',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',464,'2025-11-18','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(292,'US','OH',NULL,NULL,'cleaning','Commercial Cleaner',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',464,'2025-11-26','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(293,'US','OH',NULL,NULL,'security','Security Guard',NULL,1330,1615,2090,2508,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',121,'2025-12-01','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(294,'US','OH',NULL,NULL,'security','Event Security',NULL,1425,1805,2375,2850,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',483,'2025-12-06','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(295,'US','OH',NULL,NULL,'delivery','Delivery Driver',NULL,1330,1710,2185,2622,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',173,'2025-12-08','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(296,'US','OH',NULL,NULL,'delivery','Courier',NULL,1235,1520,1995,2394,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',263,'2025-12-01','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(297,'US','GA',NULL,NULL,'hospitality','Server',NULL,1140,1425,1900,2280,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',341,'2025-12-06','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(298,'US','GA',NULL,NULL,'hospitality','Bartender',NULL,1330,1710,2375,2850,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',495,'2025-11-20','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(299,'US','GA',NULL,NULL,'hospitality','Host/Hostess',NULL,1045,1330,1710,2052,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',360,'2025-12-08','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(300,'US','GA',NULL,NULL,'hospitality','Busser',NULL,950,1235,1520,1824,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',234,'2025-12-16','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(301,'US','GA',NULL,NULL,'hospitality','Line Cook',NULL,1330,1615,2090,2508,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',187,'2025-11-24','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(302,'US','GA',NULL,NULL,'hospitality','Prep Cook',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',59,'2025-11-27','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(303,'US','GA',NULL,NULL,'hospitality','Dishwasher',NULL,950,1235,1520,1824,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',481,'2025-11-28','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(304,'US','GA',NULL,NULL,'hospitality','Barista',NULL,1045,1330,1710,2052,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',388,'2025-12-17','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(305,'US','GA',NULL,NULL,'retail','Cashier',NULL,1045,1330,1615,1938,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',175,'2025-11-28','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(306,'US','GA',NULL,NULL,'retail','Sales Associate',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',246,'2025-11-30','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(307,'US','GA',NULL,NULL,'retail','Stock Associate',NULL,1045,1330,1615,1938,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',281,'2025-12-01','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(308,'US','GA',NULL,NULL,'retail','Visual Merchandiser',NULL,1330,1615,1995,2394,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',448,'2025-11-21','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(309,'US','GA',NULL,NULL,'retail','Customer Service Rep',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',438,'2025-11-30','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(310,'US','GA',NULL,NULL,'warehouse','Picker/Packer',NULL,1235,1520,1900,2280,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',372,'2025-12-10','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(311,'US','GA',NULL,NULL,'warehouse','Forklift Operator',NULL,1425,1805,2280,2736,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',133,'2025-11-18','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(312,'US','GA',NULL,NULL,'warehouse','Warehouse Associate',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',467,'2025-12-02','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(313,'US','GA',NULL,NULL,'warehouse','Loader/Unloader',NULL,1235,1520,1900,2280,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',193,'2025-11-29','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(314,'US','GA',NULL,NULL,'warehouse','Inventory Clerk',NULL,1235,1520,1900,2280,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',494,'2025-12-04','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(315,'US','GA',NULL,NULL,'events','Event Server',NULL,1330,1710,2280,2736,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',487,'2025-11-22','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(316,'US','GA',NULL,NULL,'events','Event Bartender',NULL,1520,2090,2850,3420,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',365,'2025-12-06','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(317,'US','GA',NULL,NULL,'events','Catering Staff',NULL,1235,1615,2090,2508,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',236,'2025-11-28','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(318,'US','GA',NULL,NULL,'events','Event Setup',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',93,'2025-12-09','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(319,'US','GA',NULL,NULL,'events','Brand Ambassador',NULL,1425,1900,2660,3192,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',65,'2025-11-24','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(320,'US','GA',NULL,NULL,'healthcare','CNA',NULL,1330,1710,2185,2622,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',270,'2025-12-12','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(321,'US','GA',NULL,NULL,'healthcare','Medical Assistant',NULL,1425,1805,2280,2736,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',143,'2025-12-03','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(322,'US','GA',NULL,NULL,'healthcare','Patient Transporter',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',232,'2025-11-30','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(323,'US','GA',NULL,NULL,'healthcare','Dietary Aide',NULL,1045,1330,1710,2052,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',411,'2025-11-26','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(324,'US','GA',NULL,NULL,'office','Receptionist',NULL,1235,1520,1900,2280,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',98,'2025-12-16','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(325,'US','GA',NULL,NULL,'office','Data Entry',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',249,'2025-12-04','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(326,'US','GA',NULL,NULL,'office','Administrative Assistant',NULL,1330,1710,2185,2622,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',264,'2025-12-13','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(327,'US','GA',NULL,NULL,'cleaning','Janitor',NULL,1045,1330,1710,2052,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',393,'2025-12-10','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(328,'US','GA',NULL,NULL,'cleaning','Housekeeper',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',60,'2025-12-15','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(329,'US','GA',NULL,NULL,'cleaning','Commercial Cleaner',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',157,'2025-12-17','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(330,'US','GA',NULL,NULL,'security','Security Guard',NULL,1330,1615,2090,2508,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',235,'2025-12-04','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(331,'US','GA',NULL,NULL,'security','Event Security',NULL,1425,1805,2375,2850,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',269,'2025-12-01','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(332,'US','GA',NULL,NULL,'delivery','Delivery Driver',NULL,1330,1710,2185,2622,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',256,'2025-11-21','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(333,'US','GA',NULL,NULL,'delivery','Courier',NULL,1235,1520,1995,2394,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',366,'2025-12-15','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(334,'US','NC',NULL,NULL,'hospitality','Server',NULL,1140,1425,1900,2280,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',120,'2025-11-25','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(335,'US','NC',NULL,NULL,'hospitality','Bartender',NULL,1330,1710,2375,2850,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',457,'2025-12-07','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(336,'US','NC',NULL,NULL,'hospitality','Host/Hostess',NULL,1045,1330,1710,2052,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',162,'2025-12-10','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(337,'US','NC',NULL,NULL,'hospitality','Busser',NULL,950,1235,1520,1824,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',170,'2025-11-22','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(338,'US','NC',NULL,NULL,'hospitality','Line Cook',NULL,1330,1615,2090,2508,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',118,'2025-11-27','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(339,'US','NC',NULL,NULL,'hospitality','Prep Cook',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',194,'2025-12-17','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(340,'US','NC',NULL,NULL,'hospitality','Dishwasher',NULL,950,1235,1520,1824,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',262,'2025-12-10','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(341,'US','NC',NULL,NULL,'hospitality','Barista',NULL,1045,1330,1710,2052,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',429,'2025-11-21','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(342,'US','NC',NULL,NULL,'retail','Cashier',NULL,1045,1330,1615,1938,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',202,'2025-11-26','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(343,'US','NC',NULL,NULL,'retail','Sales Associate',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',466,'2025-12-05','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(344,'US','NC',NULL,NULL,'retail','Stock Associate',NULL,1045,1330,1615,1938,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',401,'2025-11-27','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(345,'US','NC',NULL,NULL,'retail','Visual Merchandiser',NULL,1330,1615,1995,2394,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',312,'2025-11-28','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(346,'US','NC',NULL,NULL,'retail','Customer Service Rep',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',115,'2025-12-11','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(347,'US','NC',NULL,NULL,'warehouse','Picker/Packer',NULL,1235,1520,1900,2280,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',177,'2025-11-23','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(348,'US','NC',NULL,NULL,'warehouse','Forklift Operator',NULL,1425,1805,2280,2736,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',334,'2025-12-13','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(349,'US','NC',NULL,NULL,'warehouse','Warehouse Associate',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',121,'2025-12-08','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(350,'US','NC',NULL,NULL,'warehouse','Loader/Unloader',NULL,1235,1520,1900,2280,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',55,'2025-12-13','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(351,'US','NC',NULL,NULL,'warehouse','Inventory Clerk',NULL,1235,1520,1900,2280,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',438,'2025-12-04','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(352,'US','NC',NULL,NULL,'events','Event Server',NULL,1330,1710,2280,2736,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',385,'2025-12-08','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(353,'US','NC',NULL,NULL,'events','Event Bartender',NULL,1520,2090,2850,3420,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',216,'2025-12-06','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(354,'US','NC',NULL,NULL,'events','Catering Staff',NULL,1235,1615,2090,2508,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',158,'2025-12-13','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(355,'US','NC',NULL,NULL,'events','Event Setup',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',207,'2025-12-16','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(356,'US','NC',NULL,NULL,'events','Brand Ambassador',NULL,1425,1900,2660,3192,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',107,'2025-12-09','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(357,'US','NC',NULL,NULL,'healthcare','CNA',NULL,1330,1710,2185,2622,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',386,'2025-11-18','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(358,'US','NC',NULL,NULL,'healthcare','Medical Assistant',NULL,1425,1805,2280,2736,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',306,'2025-12-17','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(359,'US','NC',NULL,NULL,'healthcare','Patient Transporter',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',230,'2025-11-23','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(360,'US','NC',NULL,NULL,'healthcare','Dietary Aide',NULL,1045,1330,1710,2052,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',75,'2025-11-26','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(361,'US','NC',NULL,NULL,'office','Receptionist',NULL,1235,1520,1900,2280,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',281,'2025-11-19','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(362,'US','NC',NULL,NULL,'office','Data Entry',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',387,'2025-12-01','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(363,'US','NC',NULL,NULL,'office','Administrative Assistant',NULL,1330,1710,2185,2622,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',345,'2025-11-20','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(364,'US','NC',NULL,NULL,'cleaning','Janitor',NULL,1045,1330,1710,2052,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',138,'2025-12-17','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(365,'US','NC',NULL,NULL,'cleaning','Housekeeper',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',121,'2025-12-15','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(366,'US','NC',NULL,NULL,'cleaning','Commercial Cleaner',NULL,1140,1425,1805,2166,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',387,'2025-12-04','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(367,'US','NC',NULL,NULL,'security','Security Guard',NULL,1330,1615,2090,2508,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',164,'2025-12-13','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(368,'US','NC',NULL,NULL,'security','Event Security',NULL,1425,1805,2375,2850,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',72,'2025-11-18','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(369,'US','NC',NULL,NULL,'delivery','Delivery Driver',NULL,1330,1710,2185,2622,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',54,'2025-12-02','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15'),(370,'US','NC',NULL,NULL,'delivery','Courier',NULL,1235,1520,1995,2394,1.15,1.10,1.50,1.25,'USD',-200,300,'platform',274,'2025-12-06','2025-01-01','2025-12-31',1,'2025-12-18 16:52:15','2025-12-18 16:52:15');
/*!40000 ALTER TABLE `market_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `market_statistics`
--

DROP TABLE IF EXISTS `market_statistics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `market_statistics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `region` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'global',
  `shifts_live` int NOT NULL DEFAULT '0',
  `total_value` decimal(12,2) NOT NULL DEFAULT '0.00',
  `avg_hourly_rate` decimal(8,2) NOT NULL DEFAULT '0.00',
  `rate_change_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `filled_today` int NOT NULL DEFAULT '0',
  `workers_online` int NOT NULL DEFAULT '0',
  `calculated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `market_statistics_region_index` (`region`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `market_statistics`
--

LOCK TABLES `market_statistics` WRITE;
/*!40000 ALTER TABLE `market_statistics` DISABLE KEYS */;
/*!40000 ALTER TABLE `market_statistics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `message_moderation_logs`
--

DROP TABLE IF EXISTS `message_moderation_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_moderation_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `moderatable_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `moderatable_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `original_content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `moderated_content` text COLLATE utf8mb4_unicode_ci,
  `detected_issues` json DEFAULT NULL,
  `action` enum('allowed','flagged','blocked','redacted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'allowed',
  `severity` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'low',
  `requires_review` tinyint(1) NOT NULL DEFAULT '0',
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `message_moderation_logs_reviewed_by_foreign` (`reviewed_by`),
  KEY `message_moderation_logs_moderatable_type_moderatable_id_index` (`moderatable_type`,`moderatable_id`),
  KEY `message_moderation_logs_action_requires_review_index` (`action`,`requires_review`),
  KEY `message_moderation_logs_severity_requires_review_index` (`severity`,`requires_review`),
  KEY `message_moderation_logs_user_id_index` (`user_id`),
  CONSTRAINT `message_moderation_logs_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`),
  CONSTRAINT `message_moderation_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `message_moderation_logs`
--

LOCK TABLES `message_moderation_logs` WRITE;
/*!40000 ALTER TABLE `message_moderation_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `message_moderation_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `message_reads`
--

DROP TABLE IF EXISTS `message_reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_reads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `message_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `read_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `message_reads_message_id_user_id_unique` (`message_id`,`user_id`),
  KEY `message_reads_user_id_read_at_index` (`user_id`,`read_at`),
  CONSTRAINT `message_reads_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `message_reads_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `message_reads`
--

LOCK TABLES `message_reads` WRITE;
/*!40000 ALTER TABLE `message_reads` DISABLE KEYS */;
/*!40000 ALTER TABLE `message_reads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint unsigned NOT NULL,
  `from_user_id` bigint unsigned NOT NULL,
  `to_user_id` bigint unsigned NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `message_type` enum('text','image','file','system') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  `attachment_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachments` json DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `is_edited` tinyint(1) NOT NULL DEFAULT '0',
  `edited_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_conversation_id_index` (`conversation_id`),
  KEY `messages_to_user_id_is_read_index` (`to_user_id`,`is_read`),
  KEY `messages_created_at_index` (`created_at`),
  KEY `messages_message_type_index` (`message_type`),
  KEY `messages_deleted_at_index` (`deleted_at`),
  CONSTRAINT `messages_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=284 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2014_10_12_000000_create_users_table',1),(2,'2014_10_12_100000_create_password_resets_table',1),(3,'2019_08_19_000000_create_failed_jobs_table',1),(4,'2019_12_14_000001_create_personal_access_tokens_table',1),(5,'2025_12_13_210220_create_admin_settings_table',1),(6,'2025_12_13_220001_create_worker_profiles_table',1),(7,'2025_12_13_220002_create_business_profiles_table',1),(8,'2025_12_13_220003_create_agency_profiles_table',1),(9,'2025_12_13_220005_create_shift_templates_table',1),(10,'2025_12_13_220006_create_skills_table',1),(11,'2025_12_13_220007_create_certifications_table',1),(12,'2025_12_13_230001_create_worker_availability_schedules_table',1),(13,'2025_12_13_230002_create_worker_blackout_dates_table',1),(14,'2025_12_13_230003_create_worker_badges_table',1),(15,'2025_12_13_230004_create_availability_broadcasts_table',1),(16,'2025_12_13_230005_create_agency_workers_table',1),(17,'2025_12_13_230006_rename_story_to_bio_in_users_table',1),(18,'2025_12_13_230007_create_conversations_table',1),(19,'2025_12_13_230008_create_messages_table',1),(20,'2025_12_13_230009_create_worker_skills_table',1),(21,'2025_12_14_000001_create_shifts_table',1),(22,'2025_12_14_002011_add_username_role_status_to_users_table',1),(23,'2025_12_14_010001_create_shift_applications_table',1),(24,'2025_12_14_010002_create_shift_assignments_table',1),(25,'2025_12_14_010003_create_shift_attachments_table',1),(26,'2025_12_14_010004_create_shift_invitations_table',1),(27,'2025_12_14_020001_create_shift_payments_table',1),(28,'2025_12_14_020002_create_ratings_table',1),(29,'2025_12_14_020003_create_shift_swaps_table',1),(30,'2025_12_14_020004_create_shift_notifications_table',1),(31,'2025_12_14_030001_add_certificate_fields_to_worker_certifications_table',1),(32,'2025_12_14_030002_add_additional_fields_to_worker_profiles_table',1),(33,'2025_12_14_030003_add_additional_fields_to_business_profiles_table',1),(34,'2025_12_14_030004_add_additional_fields_to_agency_profiles_table',1),(35,'2025_12_14_030005_add_complete_worker_management_fields_to_worker_profiles_table',1),(36,'2025_12_14_030006_add_business_management_fields_to_business_profiles_table',1),(37,'2025_12_14_030007_add_agency_management_fields_to_agency_profiles_table',1),(38,'2025_12_14_040001_add_business_logic_to_shift_applications_table',1),(39,'2025_12_14_040002_add_agency_fields_to_shift_assignments_table',1),(40,'2025_12_14_040003_add_business_logic_to_shift_assignments_table',1),(41,'2025_12_14_040004_add_agency_commission_to_shift_payments_table',1),(42,'2025_12_14_040005_add_financial_management_fields_to_shift_payments_table',1),(43,'2025_12_14_050001_create_platform_admin_tables',1),(44,'2025_12_14_143238_add_dev_expires_at_to_users_table',1),(45,'2025_12_14_151545_create_countries_table',1),(46,'2025_12_14_151546_create_states_table',1),(47,'2025_12_14_151547_create_tax_rates_table',1),(48,'2025_12_14_151550_create_legacy_notifications_table',1),(49,'2025_12_14_162045_add_read_at_to_notifications_table',1),(50,'2025_12_14_164141_create_agency_clients_table',1),(51,'2025_12_14_164143_create_market_statistics_table',1),(52,'2025_12_14_171803_create_jobs_table',1),(53,'2025_12_14_232011_add_filled_at_to_shifts_table',1),(54,'2025_12_15_000001_create_team_members_table',1),(55,'2025_12_15_000002_add_performance_indexes',1),(56,'2025_12_15_000003_add_budget_fields_to_business_profiles_table',1),(57,'2025_12_15_000004_add_user_type_columns_to_users_table',1),(58,'2025_12_15_000005_create_compliance_reports_table',1),(59,'2025_12_15_000007_create_system_health_metrics_table',1),(60,'2025_12_15_000008_create_venues_table',1),(61,'2025_12_15_000009_create_business_cancellation_logs_table',1),(62,'2025_12_15_000010_add_venue_id_to_shifts_table',1),(63,'2025_12_15_052458_create_alert_configurations_table',1),(64,'2025_12_15_052502_add_sla_tracking_to_verification_queue_table',1),(65,'2025_12_15_052609_create_dispute_escalation_tables',1),(66,'2025_12_15_060001_add_suspension_fields_to_users_table',1),(67,'2025_12_15_060002_create_reliability_score_history_table',1),(68,'2025_12_15_080001_create_agency_performance_scorecards_table',1),(69,'2025_12_15_080002_create_urgent_shift_requests_table',1),(70,'2025_12_15_080003_create_agency_performance_notifications_table',1),(71,'2025_12_15_100000_create_system_setting_audits_table',1),(72,'2025_12_15_100008_create_worker_conversions_table',1),(73,'2025_12_15_100009_create_worker_penalties_table',1),(74,'2025_12_15_100010_add_credit_fields_to_business_profiles_table',1),(75,'2025_12_15_100011_create_business_credit_transactions_table',1),(76,'2025_12_15_100012_add_stripe_connect_fields_to_agency_profiles_table',1),(77,'2025_12_15_100013_create_credit_invoices_table',1),(78,'2025_12_15_100014_add_payout_fields_to_agency_workers_table',1),(79,'2025_12_15_100015_create_credit_invoice_items_table',1),(80,'2025_12_15_100016_create_penalty_appeals_table',1),(81,'2025_12_15_100017_create_refunds_table',1),(82,'2025_12_15_100018_create_worker_endorsements_table',1),(83,'2025_12_15_120000_create_time_tracking_records_table',1),(84,'2025_12_15_130001_add_worker_registration_fields_to_users_table',1),(85,'2025_12_15_130002_create_referral_codes_table',1),(86,'2025_12_15_130003_create_agency_invitations_table',1),(87,'2025_12_15_130005_create_verification_codes_table',1),(88,'2025_12_15_142401_add_account_lockout_fields_to_users_table',1),(89,'2025_12_15_142442_add_two_factor_auth_columns_to_users_table',1),(90,'2025_12_15_170001_create_worker_preferences_table',1),(91,'2025_12_15_170004_create_worker_activation_logs_table',1),(92,'2025_12_15_170005_add_availability_override_table',1),(93,'2025_12_15_170006_add_activation_fields_to_worker_profiles_table',1),(94,'2025_12_15_200001_create_business_verification_tables',1),(95,'2025_12_15_200008_create_identity_verifications_table',1),(96,'2025_12_15_200009_create_worker_featured_statuses_table',1),(97,'2025_12_15_200010_create_worker_profile_views_table',1),(98,'2025_12_15_200011_create_worker_portfolio_items_table',1),(99,'2025_12_15_200012_create_liveness_checks_table',1),(100,'2025_12_15_200013_create_business_onboarding_table',1),(101,'2025_12_15_200014_create_business_referrals_table',1),(102,'2025_12_15_200015_create_business_contacts_table',1),(103,'2025_12_15_200016_add_kyc_fields_to_worker_profiles_table',1),(104,'2025_12_15_200017_add_registration_fields_to_business_profiles_table',1),(105,'2025_12_15_200018_create_insurance_verification_tables',1),(106,'2025_12_15_200019_create_business_types_table',1),(107,'2025_12_15_200020_create_business_addresses_table',1),(108,'2025_12_15_200021_create_industries_table',1),(109,'2025_12_15_200022_create_verification_documents_table',1),(110,'2025_12_15_300001_enhance_venues_table_for_biz_reg_006',1),(111,'2025_12_15_300009_create_first_shift_progress_table',1),(112,'2025_12_15_300010_create_venue_operating_hours_table',1),(113,'2025_12_15_300012_enhance_worker_skills_table',1),(114,'2025_12_15_300013_create_business_payment_methods_table',1),(115,'2025_12_15_300014_create_certification_types_table',1),(116,'2025_12_15_300015_create_onboarding_steps_table',1),(117,'2025_12_15_300016_create_onboarding_progress_table',1),(118,'2025_12_15_300017_create_minimum_wages_table',1),(119,'2025_12_15_300018_create_team_invitations_table',1),(120,'2025_12_15_300019_create_background_checks_table',1),(121,'2025_12_15_300020_create_market_rates_table',1),(122,'2025_12_15_300021_create_team_activities_table',1),(123,'2025_12_15_300022_create_onboarding_cohorts_table',1),(124,'2025_12_15_300023_enhance_worker_certifications_table',1),(125,'2025_12_15_300024_create_right_to_work_verifications_table',1),(126,'2025_12_15_300025_create_rtw_documents_table',1),(127,'2025_12_15_300026_create_certification_documents_table',1),(128,'2025_12_15_300027_create_adjudication_cases_table',1),(129,'2025_12_15_300028_create_onboarding_reminders_table',1),(130,'2025_12_15_300029_add_stripe_customer_to_business_profiles_table',1),(131,'2025_12_15_300030_enhance_skills_table',1),(132,'2025_12_15_300031_create_team_permissions_table',1),(133,'2025_12_15_300032_create_skill_certification_requirements_table',1),(134,'2025_12_15_300033_enhance_team_members_table_for_biz_reg_008',1),(135,'2025_12_15_300034_add_wizard_fields_to_shift_templates_table',1),(136,'2025_12_15_300035_add_worker_payment_setup_fields',1),(137,'2025_12_15_300036_create_venue_managers_table',1),(138,'2025_12_15_300037_create_background_check_consents_table',1),(139,'2025_12_15_300038_create_onboarding_events_table',1),(140,'2025_12_15_400001_create_agency_applications_table',1),(141,'2025_12_15_400003_create_agency_compliance_checks_table',1),(142,'2025_12_15_400004_create_agency_commercial_agreements_table',1),(143,'2025_12_15_400005_create_agency_worker_invitations_table',1),(144,'2025_12_15_400050_add_go_live_compliance_fields_to_agency_profiles',1),(145,'2025_12_15_400051_create_agency_documents_table',1),(146,'2025_12_15_500001_add_activation_tracking_to_business_profiles_table',1),(147,'2025_12_17_221100_create_business_worker_roster_table',1),(148,'2025_12_18_000001_create_data_regions_table',1),(149,'2025_12_18_000001_create_incidents_table',1),(150,'2025_12_18_000001_create_regional_pricing_table',1),(151,'2025_12_18_000001_create_surveys_table',1),(152,'2025_12_18_000002_create_incident_updates_table',1),(153,'2025_12_18_000002_create_price_adjustments_table',1),(154,'2025_12_18_000002_create_survey_responses_table',1),(155,'2025_12_18_000002_create_user_data_residency_table',1),(156,'2025_12_18_000003_create_data_transfer_logs_table',1),(157,'2025_12_18_000003_create_feature_requests_table',1),(158,'2025_12_18_000004_create_feature_request_votes_table',1),(159,'2025_12_18_000005_create_bug_reports_table',1),(160,'2025_12_18_000006_add_post_shift_survey_sent_at_to_shift_assignments_table',1),(161,'2025_12_18_010001_enhance_conversations_table_for_com_001',1),(162,'2025_12_18_010002_create_conversation_participants_table',1),(163,'2025_12_18_010003_enhance_messages_table_for_com_001',1),(164,'2025_12_18_010004_create_message_reads_table',1),(165,'2025_12_18_100001_create_data_subject_requests_table',1),(166,'2025_12_18_100001_create_email_templates_table',1),(167,'2025_12_18_100001_create_health_declarations_table',1),(168,'2025_12_18_100001_create_integrations_table',1),(169,'2025_12_18_100001_create_tax_jurisdictions_table',1),(170,'2025_12_18_100001_create_worker_relationships_table',1),(171,'2025_12_18_100002_create_consent_records_table',1),(172,'2025_12_18_100002_create_email_logs_table',1),(173,'2025_12_18_100002_create_integration_syncs_table',1),(174,'2025_12_18_100002_create_tax_forms_table',1),(175,'2025_12_18_100002_create_vaccination_records_table',1),(176,'2025_12_18_100002_create_worker_teams_table',1),(177,'2025_12_18_100003_add_health_fields_to_shifts_table',1),(178,'2025_12_18_100003_create_data_retention_policies_table',1),(179,'2025_12_18_100003_create_email_preferences_table',1),(180,'2025_12_18_100003_create_tax_calculations_table',1),(181,'2025_12_18_100003_create_webhooks_table',1),(182,'2025_12_18_100003_create_worker_team_members_table',1),(183,'2025_12_18_100004_add_tax_fields_to_shift_payments_table',1),(184,'2025_12_18_100004_create_team_shift_requests_table',1),(185,'2025_12_18_172125_create_shift_positions_table',1),(186,'2025_12_18_172136_create_feature_flags_table',1),(187,'2025_12_18_172137_create_feature_flag_logs_table',1),(188,'2025_12_18_172212_create_shift_position_assignments_table',1),(189,'2025_12_18_173613_create_push_notification_tokens_table',1),(190,'2025_12_18_173624_create_emergency_alerts_table',1),(191,'2025_12_18_173625_create_emergency_contacts_table',1),(192,'2025_12_18_173627_create_push_notification_logs_table',1),(193,'2025_12_18_173644_add_category_ratings_to_ratings_table',1),(194,'2025_12_18_173715_add_rating_category_averages_to_profiles',1),(195,'2025_12_18_175019_create_message_moderation_logs_table',1),(196,'2025_12_18_175025_create_blocked_phrases_table',1),(197,'2025_12_18_175025_create_communication_reports_table',1),(198,'2025_12_18_175033_create_availability_patterns_table',1),(199,'2025_12_18_175033_create_availability_predictions_table',1),(200,'2025_12_18_175034_create_demand_forecasts_table',1),(201,'2025_12_18_180001_create_disputes_table',1),(202,'2025_12_18_180722_create_demand_metrics_table',1),(203,'2025_12_18_180722_create_surge_events_table',1),(204,'2025_12_18_180724_create_public_holidays_table',1),(205,'2025_12_18_180751_create_holiday_calendars_table',1),(206,'2025_12_18_182640_create_face_profiles_table',1),(207,'2025_12_18_182647_create_volume_discount_tiers_table',1),(208,'2025_12_18_182655_create_whatsapp_templates_table',1),(209,'2025_12_18_182710_create_face_verification_logs_table',1),(210,'2025_12_18_182711_create_business_volume_tracking_table',1),(211,'2025_12_18_182718_create_sms_logs_table',1),(212,'2025_12_18_182734_add_volume_discount_fields_to_business_profiles_table',1),(213,'2025_12_18_182742_create_user_phone_preferences_table',1),(214,'2025_12_18_184756_create_labor_law_rules_table',1),(215,'2025_12_18_184757_create_compliance_violations_table',1),(216,'2025_12_18_184757_create_worker_exemptions_table',1),(217,'2025_12_18_184805_create_audit_checklists_table',1),(218,'2025_12_18_184805_create_mystery_shoppers_table',1),(219,'2025_12_18_184805_create_shift_audits_table',1),(220,'2025_12_18_184807_create_instapay_requests_table',1),(221,'2025_12_18_184813_create_instapay_settings_table',1),(222,'2025_12_18_190152_add_labor_law_rule_foreign_key_to_compliance_violations',1),(223,'2025_12_18_191026_create_business_rosters_table',1),(224,'2025_12_18_191031_create_agency_tiers_table',1),(225,'2025_12_18_191037_create_kyc_verifications_table',1),(226,'2025_12_18_191051_create_roster_members_table',1),(227,'2025_12_18_191100_add_tier_fields_to_agency_profiles_table',1),(228,'2025_12_18_191112_create_roster_invitations_table',1),(229,'2025_12_18_191119_add_kyc_fields_to_users_table',1),(230,'2025_12_18_191126_create_agency_tier_history_table',1),(231,'2025_12_18_193716_create_safety_certifications_table',1),(232,'2025_12_18_193749_add_safety_certification_to_worker_certifications_table',1),(233,'2025_12_18_193749_create_shift_certification_requirements_table',1),(234,'2025_12_18_200325_create_improvement_suggestions_table',1),(235,'2025_12_18_200325_create_suggestion_votes_table',1),(236,'2025_12_18_200326_create_improvement_metrics_table',1),(237,'2025_12_18_200352_create_worker_tiers_table',1),(238,'2025_12_18_200356_create_worker_suspensions_table',1),(239,'2025_12_18_200407_create_worker_earnings_table',1),(240,'2025_12_18_200408_create_communication_templates_table',1),(241,'2025_12_18_200408_create_template_sends_table',1),(242,'2025_12_18_200412_create_earnings_summaries_table',1),(243,'2025_12_18_200419_add_career_tier_fields_to_worker_profiles_table',1),(244,'2025_12_18_200435_create_suspension_appeals_table',1),(245,'2025_12_18_200440_create_worker_tier_history_table',1),(246,'2025_12_18_200452_add_strike_fields_to_users_table',1),(247,'2025_12_18_204739_add_hours_columns_to_worker_earnings_table',1),(248,'2025_12_18_600001_create_loyalty_points_table',1),(249,'2025_12_18_600002_create_loyalty_transactions_table',1),(250,'2025_12_18_600003_create_loyalty_rewards_table',1),(251,'2025_12_18_600004_create_loyalty_redemptions_table',1),(252,'2025_12_18_700001_create_fraud_signals_table',1),(253,'2025_12_18_700002_create_fraud_rules_table',1),(254,'2025_12_18_700003_create_user_risk_scores_table',1),(255,'2025_12_18_700004_create_device_fingerprints_table',1),(256,'2025_12_18_800001_create_currency_wallets_table',1),(257,'2025_12_18_800001_create_payment_corridors_table',1),(258,'2025_12_18_800002_create_bank_accounts_table',1),(259,'2025_12_18_800002_create_exchange_rates_table',1),(260,'2025_12_18_800003_create_cross_border_transfers_table',1),(261,'2025_12_18_800003_create_currency_conversions_table',1),(262,'2025_12_18_900001_create_venue_safety_ratings_table',1),(263,'2025_12_18_900001_create_white_label_configs_table',1),(264,'2025_12_18_900002_create_venue_safety_flags_table',1),(265,'2025_12_18_900002_create_white_label_domains_table',1),(266,'2025_12_18_900003_add_safety_fields_to_venues_table',1),(267,'2025_12_18_950001_create_locales_table',1),(268,'2025_12_18_950002_add_locale_to_users_table',1),(269,'2025_12_18_950003_create_translations_table',1),(270,'2025_12_18_960001_create_tax_reports_table',1),(271,'2025_12_18_960002_create_tax_withholdings_table',1),(272,'2025_12_18_970001_create_subscription_plans_table',1),(273,'2025_12_18_970002_create_subscriptions_table',1),(274,'2025_12_18_970003_create_subscription_invoices_table',1),(275,'2025_12_19_100001_create_booking_confirmations_table',1),(276,'2025_12_19_100001_create_payroll_runs_table',1),(277,'2025_12_19_100002_create_confirmation_reminders_table',1),(278,'2025_12_19_100002_create_payroll_items_table',1),(279,'2025_12_19_100003_create_payroll_deductions_table',1),(280,'2025_12_19_132431_add_performance_indexes_for_5000_concurrent_users',2),(281,'2025_12_19_175137_make_certification_id_nullable_in_worker_certifications',3),(282,'2025_12_19_000001_create_withdrawals_and_payout_methods_tables',4),(283,'2025_12_19_221727_make_author_nullable_in_notifications_table',5);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `minimum_wages`
--

DROP TABLE IF EXISTS `minimum_wages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `minimum_wages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `country_code` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `state_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jurisdiction_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hourly_rate_cents` int NOT NULL,
  `tipped_rate_cents` int DEFAULT NULL,
  `youth_rate_cents` int DEFAULT NULL,
  `overtime_rate_cents` int DEFAULT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `effective_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `rate_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'standard',
  `conditions` json DEFAULT NULL,
  `overtime_multiplier` decimal(3,2) NOT NULL DEFAULT '1.50',
  `overtime_threshold_daily` int DEFAULT NULL,
  `overtime_threshold_weekly` int NOT NULL DEFAULT '40',
  `source` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_verified_at` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_federal` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mw_jurisdiction_rate_unique` (`country_code`,`state_code`,`city`,`rate_type`,`effective_date`),
  KEY `minimum_wages_country_code_state_code_city_index` (`country_code`,`state_code`,`city`),
  KEY `minimum_wages_country_code_effective_date_index` (`country_code`,`effective_date`),
  KEY `minimum_wages_is_active_effective_date_index` (`is_active`,`effective_date`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `minimum_wages`
--

LOCK TABLES `minimum_wages` WRITE;
/*!40000 ALTER TABLE `minimum_wages` DISABLE KEYS */;
INSERT INTO `minimum_wages` VALUES (1,'US',NULL,NULL,'United States (Federal)',725,213,NULL,NULL,'USD','2009-07-24',NULL,'standard',NULL,1.50,NULL,40,'U.S. Department of Labor','https://www.dol.gov/agencies/whd/minimum-wage',NULL,1,1,'2025-12-18 16:52:14','2025-12-18 16:52:14'),(2,'US','CA',NULL,'California',1600,1600,NULL,NULL,'USD','2024-01-01',NULL,'standard',NULL,1.50,NULL,40,'CA DIR',NULL,NULL,1,0,'2025-12-18 16:52:14','2025-12-18 16:52:14'),(3,'US','NY',NULL,'New York',1500,1250,NULL,NULL,'USD','2024-01-01',NULL,'standard',NULL,1.50,NULL,40,'NY DOL',NULL,NULL,1,0,'2025-12-18 16:52:14','2025-12-18 16:52:14'),(4,'US','WA',NULL,'Washington',1628,1628,NULL,NULL,'USD','2024-01-01',NULL,'standard',NULL,1.50,NULL,40,'WA L&I',NULL,NULL,1,0,'2025-12-18 16:52:14','2025-12-18 16:52:14'),(5,'US','TX',NULL,'Texas',725,213,NULL,NULL,'USD','2009-07-24',NULL,'standard',NULL,1.50,NULL,40,'TX TWC',NULL,NULL,1,0,'2025-12-18 16:52:14','2025-12-18 16:52:14'),(6,'US','FL',NULL,'Florida',1300,900,NULL,NULL,'USD','2024-09-30',NULL,'standard',NULL,1.50,NULL,40,'FL DEO',NULL,NULL,1,0,'2025-12-18 16:52:14','2025-12-18 16:52:14'),(7,'US','CA','San Francisco','San Francisco, CA',1807,1807,NULL,NULL,'USD','2024-07-01',NULL,'standard',NULL,1.50,NULL,40,'SF OLSE',NULL,NULL,1,0,'2025-12-18 16:52:14','2025-12-18 16:52:14'),(8,'US','WA','Seattle','Seattle, WA',1950,1950,NULL,NULL,'USD','2024-01-01',NULL,'standard',NULL,1.50,NULL,40,'Seattle OLS',NULL,NULL,1,0,'2025-12-18 16:52:14','2025-12-18 16:52:14'),(9,'US','NY','New York City','New York City, NY',1600,1600,NULL,NULL,'USD','2024-01-01',NULL,'standard',NULL,1.50,NULL,40,'NYC Consumer Affairs',NULL,NULL,1,0,'2025-12-18 16:52:14','2025-12-18 16:52:14'),(10,'GB',NULL,NULL,'United Kingdom',1142,NULL,842,NULL,'GBP','2024-04-01',NULL,'standard',NULL,1.50,NULL,40,'UK Government','https://www.gov.uk/national-minimum-wage-rates',NULL,1,0,'2025-12-18 16:52:14','2025-12-18 16:52:14'),(11,'DE',NULL,NULL,'Germany',1241,NULL,NULL,NULL,'EUR','2024-01-01',NULL,'standard',NULL,1.50,NULL,40,'German Federal Government',NULL,NULL,1,0,'2025-12-18 16:52:14','2025-12-18 16:52:14'),(12,'FR',NULL,NULL,'France',1178,NULL,NULL,NULL,'EUR','2024-01-01',NULL,'standard',NULL,1.50,NULL,40,'French Government',NULL,NULL,1,0,'2025-12-18 16:52:14','2025-12-18 16:52:14'),(13,'NL',NULL,NULL,'Netherlands',1316,NULL,NULL,NULL,'EUR','2024-01-01',NULL,'standard',NULL,1.50,NULL,40,'Dutch Government',NULL,NULL,1,0,'2025-12-18 16:52:14','2025-12-18 16:52:14'),(14,'AU',NULL,NULL,'Australia',2333,NULL,NULL,NULL,'AUD','2024-07-01',NULL,'standard',NULL,1.50,NULL,40,'Fair Work Commission',NULL,NULL,1,0,'2025-12-18 16:52:14','2025-12-18 16:52:14'),(15,'CA',NULL,NULL,'Canada (Federal)',1765,NULL,NULL,NULL,'CAD','2024-04-01',NULL,'standard',NULL,1.50,NULL,40,'Government of Canada',NULL,NULL,1,1,'2025-12-18 16:52:14','2025-12-18 16:52:14'),(16,'CA','ON',NULL,'Ontario, Canada',1655,NULL,NULL,NULL,'CAD','2024-10-01',NULL,'standard',NULL,1.50,NULL,40,'Ontario Ministry of Labour',NULL,NULL,1,0,'2025-12-18 16:52:14','2025-12-18 16:52:14'),(17,'CA','BC',NULL,'British Columbia, Canada',1762,NULL,NULL,NULL,'CAD','2024-06-01',NULL,'standard',NULL,1.50,NULL,40,'BC Employment Standards',NULL,NULL,1,0,'2025-12-18 16:52:14','2025-12-18 16:52:14');
/*!40000 ALTER TABLE `minimum_wages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mystery_shoppers`
--

DROP TABLE IF EXISTS `mystery_shoppers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mystery_shoppers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `audits_completed` int NOT NULL DEFAULT '0',
  `avg_quality_score` decimal(5,2) DEFAULT NULL,
  `specializations` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mystery_shoppers_user_id_foreign` (`user_id`),
  KEY `mystery_shoppers_is_active_index` (`is_active`),
  KEY `mystery_shoppers_audits_completed_index` (`audits_completed`),
  CONSTRAINT `mystery_shoppers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mystery_shoppers`
--

LOCK TABLES `mystery_shoppers` WRITE;
/*!40000 ALTER TABLE `mystery_shoppers` DISABLE KEYS */;
/*!40000 ALTER TABLE `mystery_shoppers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `destination` bigint unsigned NOT NULL,
  `author` bigint unsigned DEFAULT NULL,
  `type` int NOT NULL,
  `target` bigint unsigned DEFAULT NULL,
  `read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `notifications_destination_foreign` (`destination`),
  KEY `notifications_author_foreign` (`author`),
  CONSTRAINT `notifications_author_foreign` FOREIGN KEY (`author`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_destination_foreign` FOREIGN KEY (`destination`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `onboarding_cohorts`
--

DROP TABLE IF EXISTS `onboarding_cohorts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `onboarding_cohorts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cohort_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Unique cohort identifier',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Human-readable cohort name',
  `description` text COLLATE utf8mb4_unicode_ci,
  `experiment_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Name of the A/B test experiment',
  `user_type` enum('worker','business','agency','all') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `variant` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'control, variant_a, variant_b, etc.',
  `allocation_percentage` smallint unsigned NOT NULL DEFAULT '50' COMMENT 'Traffic allocation 0-100',
  `start_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `status` enum('draft','active','paused','completed','winner') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `is_winner` tinyint(1) NOT NULL DEFAULT '0',
  `total_users` int unsigned NOT NULL DEFAULT '0',
  `completed_users` int unsigned NOT NULL DEFAULT '0',
  `completion_rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '0.00 to 100.00',
  `avg_time_to_activation_hours` decimal(10,2) DEFAULT NULL,
  `dropout_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `step_completion_rates` json DEFAULT NULL COMMENT 'Completion rate per step',
  `step_dropout_rates` json DEFAULT NULL COMMENT 'Dropout rate per step',
  `step_avg_times` json DEFAULT NULL COMMENT 'Average time per step',
  `statistical_significance` decimal(5,2) DEFAULT NULL COMMENT 'P-value or confidence',
  `comparison_data` json DEFAULT NULL COMMENT 'Detailed comparison metrics',
  `configuration` json DEFAULT NULL COMMENT 'Custom configuration for this cohort',
  `created_by` bigint unsigned DEFAULT NULL,
  `declared_winner_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `onboarding_cohorts_cohort_id_unique` (`cohort_id`),
  KEY `onboarding_cohorts_created_by_foreign` (`created_by`),
  KEY `onboarding_cohorts_experiment_name_status_index` (`experiment_name`,`status`),
  KEY `onboarding_cohorts_user_type_status_index` (`user_type`,`status`),
  KEY `onboarding_cohorts_start_date_end_date_index` (`start_date`,`end_date`),
  KEY `onboarding_cohorts_status_index` (`status`),
  CONSTRAINT `onboarding_cohorts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `onboarding_cohorts`
--

LOCK TABLES `onboarding_cohorts` WRITE;
/*!40000 ALTER TABLE `onboarding_cohorts` DISABLE KEYS */;
/*!40000 ALTER TABLE `onboarding_cohorts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `onboarding_events`
--

DROP TABLE IF EXISTS `onboarding_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `onboarding_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `event_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'onboarding_started, step_started, step_completed, etc.',
  `step_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Reference to step_id in onboarding_steps',
  `metadata` json DEFAULT NULL COMMENT 'Additional event data',
  `source` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'web, mobile, api, admin',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `duration_seconds` int unsigned DEFAULT NULL COMMENT 'Duration of the event/action',
  `session_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cohort_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cohort_variant` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `related_event_id` bigint unsigned DEFAULT NULL COMMENT 'Link to related event',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `onboarding_events_user_id_event_type_index` (`user_id`,`event_type`),
  KEY `onboarding_events_event_type_created_at_index` (`event_type`,`created_at`),
  KEY `onboarding_events_step_id_event_type_index` (`step_id`,`event_type`),
  KEY `onboarding_events_cohort_id_event_type_index` (`cohort_id`,`event_type`),
  KEY `onboarding_events_session_id_index` (`session_id`),
  KEY `onboarding_events_created_at_index` (`created_at`),
  CONSTRAINT `onboarding_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `onboarding_events`
--

LOCK TABLES `onboarding_events` WRITE;
/*!40000 ALTER TABLE `onboarding_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `onboarding_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `onboarding_progress`
--

DROP TABLE IF EXISTS `onboarding_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `onboarding_progress` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `onboarding_step_id` bigint unsigned NOT NULL,
  `status` enum('pending','in_progress','completed','failed','skipped') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `progress_percentage` int unsigned NOT NULL DEFAULT '0' COMMENT '0-100 for partial completion',
  `progress_data` json DEFAULT NULL COMMENT 'Additional step-specific progress data',
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `time_spent_seconds` int unsigned NOT NULL DEFAULT '0' COMMENT 'Total time spent on step',
  `attempt_count` smallint unsigned NOT NULL DEFAULT '0' COMMENT 'Number of attempts',
  `completed_by` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'user, system, admin, auto',
  `completion_notes` text COLLATE utf8mb4_unicode_ci,
  `skipped_at` timestamp NULL DEFAULT NULL,
  `skip_reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `failure_reason` text COLLATE utf8mb4_unicode_ci,
  `last_reminder_at` timestamp NULL DEFAULT NULL,
  `reminder_count` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `op_user_step_unique` (`user_id`,`onboarding_step_id`),
  KEY `onboarding_progress_onboarding_step_id_foreign` (`onboarding_step_id`),
  KEY `op_user_status_idx` (`user_id`,`status`),
  KEY `op_status_started_idx` (`status`,`started_at`),
  KEY `op_completed_at_idx` (`completed_at`),
  CONSTRAINT `onboarding_progress_onboarding_step_id_foreign` FOREIGN KEY (`onboarding_step_id`) REFERENCES `onboarding_steps` (`id`) ON DELETE CASCADE,
  CONSTRAINT `onboarding_progress_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `onboarding_progress`
--

LOCK TABLES `onboarding_progress` WRITE;
/*!40000 ALTER TABLE `onboarding_progress` DISABLE KEYS */;
/*!40000 ALTER TABLE `onboarding_progress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `onboarding_reminders`
--

DROP TABLE IF EXISTS `onboarding_reminders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `onboarding_reminders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `reminder_type` enum('welcome','first_step','incomplete_step','inactivity','milestone','completion_nudge','celebration','special_offer','support_offer') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'incomplete_step',
  `step_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scheduled_at` timestamp NOT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `opened_at` timestamp NULL DEFAULT NULL,
  `clicked_at` timestamp NULL DEFAULT NULL,
  `status` enum('scheduled','sent','delivered','opened','clicked','cancelled','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `channel` enum('email','push','sms','in_app') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email',
  `subject` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `template_data` json DEFAULT NULL,
  `tracking_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_responded_at` timestamp NULL DEFAULT NULL,
  `response_action` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'completed_step, visited_dashboard, etc.',
  `is_suppressed` tinyint(1) NOT NULL DEFAULT '0',
  `suppression_reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `onboarding_reminders_tracking_id_unique` (`tracking_id`),
  KEY `onboarding_reminders_user_id_status_index` (`user_id`,`status`),
  KEY `onboarding_reminders_scheduled_at_status_index` (`scheduled_at`,`status`),
  KEY `onboarding_reminders_reminder_type_status_index` (`reminder_type`,`status`),
  KEY `onboarding_reminders_step_id_index` (`step_id`),
  CONSTRAINT `onboarding_reminders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `onboarding_reminders`
--

LOCK TABLES `onboarding_reminders` WRITE;
/*!40000 ALTER TABLE `onboarding_reminders` DISABLE KEYS */;
/*!40000 ALTER TABLE `onboarding_reminders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `onboarding_steps`
--

DROP TABLE IF EXISTS `onboarding_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `onboarding_steps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `step_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Unique identifier: account_created, email_verified, etc.',
  `user_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'worker, business, agency',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Human-readable step name',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'Detailed description shown to users',
  `help_text` text COLLATE utf8mb4_unicode_ci COMMENT 'Context-specific help content',
  `help_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Link to detailed help documentation',
  `step_type` enum('required','recommended','optional') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'required',
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'verification, profile, compliance, payment, etc.',
  `order` int unsigned NOT NULL DEFAULT '0' COMMENT 'Display order within type',
  `dependencies` json DEFAULT NULL COMMENT 'Array of step_ids that must be completed first',
  `weight` int unsigned NOT NULL DEFAULT '10' COMMENT 'Weight for progress percentage calculation',
  `estimated_minutes` int unsigned NOT NULL DEFAULT '5' COMMENT 'Estimated time to complete in minutes',
  `threshold` int unsigned DEFAULT NULL COMMENT 'Minimum percentage required (e.g., profile 80% complete)',
  `target` int unsigned DEFAULT NULL COMMENT 'Target count for countable steps (e.g., 3 skills)',
  `auto_complete` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Whether step can be auto-completed by system',
  `auto_complete_event` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Event that triggers auto-completion',
  `route_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Route to navigate user for completion',
  `route_params` json DEFAULT NULL COMMENT 'Parameters for the route',
  `icon` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT 'check-circle' COMMENT 'Icon class for display',
  `color` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT 'blue' COMMENT 'Color theme for step',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `cohort_variant` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'A/B test variant this step belongs to',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `onboarding_steps_step_id_unique` (`step_id`),
  KEY `os_type_step_order_idx` (`user_type`,`step_type`,`order`),
  KEY `os_type_active_idx` (`user_type`,`is_active`),
  KEY `os_cohort_variant_idx` (`cohort_variant`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `onboarding_steps`
--

LOCK TABLES `onboarding_steps` WRITE;
/*!40000 ALTER TABLE `onboarding_steps` DISABLE KEYS */;
/*!40000 ALTER TABLE `onboarding_steps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `onboarding_user_cohorts`
--

DROP TABLE IF EXISTS `onboarding_user_cohorts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `onboarding_user_cohorts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `onboarding_cohort_id` bigint unsigned NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `onboarding_user_cohorts_user_id_onboarding_cohort_id_unique` (`user_id`,`onboarding_cohort_id`),
  KEY `onboarding_user_cohorts_onboarding_cohort_id_foreign` (`onboarding_cohort_id`),
  KEY `onboarding_user_cohorts_assigned_at_index` (`assigned_at`),
  CONSTRAINT `onboarding_user_cohorts_onboarding_cohort_id_foreign` FOREIGN KEY (`onboarding_cohort_id`) REFERENCES `onboarding_cohorts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `onboarding_user_cohorts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `onboarding_user_cohorts`
--

LOCK TABLES `onboarding_user_cohorts` WRITE;
/*!40000 ALTER TABLE `onboarding_user_cohorts` DISABLE KEYS */;
/*!40000 ALTER TABLE `onboarding_user_cohorts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_adjustments`
--

DROP TABLE IF EXISTS `payment_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_adjustments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dispute_id` bigint unsigned DEFAULT NULL,
  `shift_payment_id` bigint unsigned DEFAULT NULL,
  `adjustment_type` enum('worker_payout','business_refund','split_resolution','no_adjustment','bonus','penalty','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `applied_to` enum('worker','business','both') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `worker_id` bigint unsigned DEFAULT NULL,
  `business_id` bigint unsigned DEFAULT NULL,
  `created_by_admin_id` bigint unsigned DEFAULT NULL,
  `status` enum('pending','applied','reversed','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `applied_at` timestamp NULL DEFAULT NULL,
  `reversed_at` timestamp NULL DEFAULT NULL,
  `reversal_reason` text COLLATE utf8mb4_unicode_ci,
  `stripe_transfer_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_refund_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_adjustments_dispute_id_index` (`dispute_id`),
  KEY `payment_adjustments_shift_payment_id_index` (`shift_payment_id`),
  KEY `payment_adjustments_status_created_at_index` (`status`,`created_at`),
  KEY `payment_adjustments_worker_id_index` (`worker_id`),
  KEY `payment_adjustments_business_id_index` (`business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_adjustments`
--

LOCK TABLES `payment_adjustments` WRITE;
/*!40000 ALTER TABLE `payment_adjustments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_adjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_corridors`
--

DROP TABLE IF EXISTS `payment_corridors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_corridors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `source_country` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `destination_country` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `destination_currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_method` enum('sepa','swift','ach','faster_payments','local') COLLATE utf8mb4_unicode_ci NOT NULL,
  `estimated_days_min` int NOT NULL DEFAULT '1',
  `estimated_days_max` int NOT NULL DEFAULT '5',
  `fee_fixed` decimal(10,2) NOT NULL DEFAULT '0.00',
  `fee_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `min_amount` decimal(15,2) DEFAULT NULL,
  `max_amount` decimal(15,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_corridors_unique` (`source_country`,`destination_country`,`payment_method`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_corridors`
--

LOCK TABLES `payment_corridors` WRITE;
/*!40000 ALTER TABLE `payment_corridors` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_corridors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payout_methods`
--

DROP TABLE IF EXISTS `payout_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payout_methods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `label` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_four` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payout_methods_user_id_is_active_index` (`user_id`,`is_active`),
  CONSTRAINT `payout_methods_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payout_methods`
--

LOCK TABLES `payout_methods` WRITE;
/*!40000 ALTER TABLE `payout_methods` DISABLE KEYS */;
/*!40000 ALTER TABLE `payout_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_deductions`
--

DROP TABLE IF EXISTS `payroll_deductions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_deductions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payroll_item_id` bigint unsigned NOT NULL,
  `type` enum('platform_fee','tax','garnishment','advance_repayment','uniform','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `is_percentage` tinyint(1) NOT NULL DEFAULT '0',
  `percentage_rate` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payroll_deductions_payroll_item_id_index` (`payroll_item_id`),
  KEY `payroll_deductions_type_index` (`type`),
  CONSTRAINT `payroll_deductions_payroll_item_id_foreign` FOREIGN KEY (`payroll_item_id`) REFERENCES `payroll_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_deductions`
--

LOCK TABLES `payroll_deductions` WRITE;
/*!40000 ALTER TABLE `payroll_deductions` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_deductions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_items`
--

DROP TABLE IF EXISTS `payroll_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payroll_run_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `shift_id` bigint unsigned DEFAULT NULL,
  `shift_assignment_id` bigint unsigned DEFAULT NULL,
  `type` enum('regular','overtime','bonus','adjustment','reimbursement') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'regular',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `hours` decimal(6,2) NOT NULL DEFAULT '0.00',
  `rate` decimal(10,2) NOT NULL DEFAULT '0.00',
  `gross_amount` decimal(10,2) NOT NULL,
  `deductions` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tax_withheld` decimal(10,2) NOT NULL DEFAULT '0.00',
  `net_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','paid','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_reference` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_transfer_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payroll_items_shift_assignment_id_foreign` (`shift_assignment_id`),
  KEY `payroll_items_payroll_run_id_user_id_index` (`payroll_run_id`,`user_id`),
  KEY `payroll_items_status_index` (`status`),
  KEY `payroll_items_shift_id_index` (`shift_id`),
  KEY `payroll_items_user_id_index` (`user_id`),
  CONSTRAINT `payroll_items_payroll_run_id_foreign` FOREIGN KEY (`payroll_run_id`) REFERENCES `payroll_runs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payroll_items_shift_assignment_id_foreign` FOREIGN KEY (`shift_assignment_id`) REFERENCES `shift_assignments` (`id`),
  CONSTRAINT `payroll_items_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`),
  CONSTRAINT `payroll_items_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_items`
--

LOCK TABLES `payroll_items` WRITE;
/*!40000 ALTER TABLE `payroll_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_runs`
--

DROP TABLE IF EXISTS `payroll_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_runs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `pay_date` date NOT NULL,
  `status` enum('draft','pending_approval','approved','processing','completed','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `total_workers` int NOT NULL DEFAULT '0',
  `total_shifts` int NOT NULL DEFAULT '0',
  `gross_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_deductions` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_taxes` decimal(12,2) NOT NULL DEFAULT '0.00',
  `net_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `created_by` bigint unsigned NOT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payroll_runs_reference_unique` (`reference`),
  KEY `payroll_runs_created_by_foreign` (`created_by`),
  KEY `payroll_runs_approved_by_foreign` (`approved_by`),
  KEY `payroll_runs_status_index` (`status`),
  KEY `payroll_runs_period_start_index` (`period_start`),
  KEY `payroll_runs_period_end_index` (`period_end`),
  KEY `payroll_runs_pay_date_index` (`pay_date`),
  CONSTRAINT `payroll_runs_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `payroll_runs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_runs`
--

LOCK TABLES `payroll_runs` WRITE;
/*!40000 ALTER TABLE `payroll_runs` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_runs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `penalty_appeals`
--

DROP TABLE IF EXISTS `penalty_appeals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `penalty_appeals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `penalty_id` bigint unsigned NOT NULL,
  `worker_id` bigint unsigned NOT NULL,
  `reviewed_by_admin_id` bigint unsigned DEFAULT NULL,
  `appeal_reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `evidence_urls` json DEFAULT NULL,
  `additional_notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','under_review','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `decision_reason` text COLLATE utf8mb4_unicode_ci,
  `adjusted_amount` decimal(10,2) DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `deadline_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `penalty_appeals_penalty_id_index` (`penalty_id`),
  KEY `penalty_appeals_worker_id_index` (`worker_id`),
  KEY `penalty_appeals_status_submitted_at_index` (`status`,`submitted_at`),
  KEY `penalty_appeals_reviewed_by_admin_id_index` (`reviewed_by_admin_id`),
  KEY `penalty_appeals_deadline_at_index` (`deadline_at`),
  KEY `penalty_appeals_status_index` (`status`),
  CONSTRAINT `penalty_appeals_penalty_id_foreign` FOREIGN KEY (`penalty_id`) REFERENCES `worker_penalties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `penalty_appeals_reviewed_by_admin_id_foreign` FOREIGN KEY (`reviewed_by_admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `penalty_appeals_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `penalty_appeals`
--

LOCK TABLES `penalty_appeals` WRITE;
/*!40000 ALTER TABLE `penalty_appeals` DISABLE KEYS */;
/*!40000 ALTER TABLE `penalty_appeals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `platform_analytics`
--

DROP TABLE IF EXISTS `platform_analytics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `platform_analytics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `total_shifts_posted` int NOT NULL DEFAULT '0',
  `total_shifts_filled` int NOT NULL DEFAULT '0',
  `total_shifts_completed` int NOT NULL DEFAULT '0',
  `platform_revenue` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_gmv` decimal(12,2) NOT NULL DEFAULT '0.00',
  `new_workers` int NOT NULL DEFAULT '0',
  `new_businesses` int NOT NULL DEFAULT '0',
  `new_agencies` int NOT NULL DEFAULT '0',
  `active_workers` int NOT NULL DEFAULT '0',
  `active_businesses` int NOT NULL DEFAULT '0',
  `average_shift_value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `fill_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `total_disputes` int NOT NULL DEFAULT '0',
  `disputes_resolved` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_analytics_date_unique` (`date`),
  KEY `platform_analytics_date_index` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platform_analytics`
--

LOCK TABLES `platform_analytics` WRITE;
/*!40000 ALTER TABLE `platform_analytics` DISABLE KEYS */;
/*!40000 ALTER TABLE `platform_analytics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `price_adjustments`
--

DROP TABLE IF EXISTS `price_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `price_adjustments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `regional_pricing_id` bigint unsigned NOT NULL,
  `adjustment_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `multiplier` decimal(5,3) NOT NULL DEFAULT '1.000',
  `fixed_adjustment` decimal(10,2) NOT NULL DEFAULT '0.00',
  `valid_from` date NOT NULL,
  `valid_until` date DEFAULT NULL,
  `conditions` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `price_adjustments_regional_pricing_id_foreign` (`regional_pricing_id`),
  KEY `price_adjustments_adjustment_type_index` (`adjustment_type`),
  KEY `price_adjustments_is_active_index` (`is_active`),
  KEY `price_adjustments_valid_from_valid_until_index` (`valid_from`,`valid_until`),
  KEY `price_adjustments_created_by_index` (`created_by`),
  CONSTRAINT `price_adjustments_regional_pricing_id_foreign` FOREIGN KEY (`regional_pricing_id`) REFERENCES `regional_pricing` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `price_adjustments`
--

LOCK TABLES `price_adjustments` WRITE;
/*!40000 ALTER TABLE `price_adjustments` DISABLE KEYS */;
/*!40000 ALTER TABLE `price_adjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `public_holidays`
--

DROP TABLE IF EXISTS `public_holidays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `public_holidays` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `country_code` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `region_code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `local_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` date NOT NULL,
  `is_national` tinyint(1) NOT NULL DEFAULT '1',
  `is_observed` tinyint(1) NOT NULL DEFAULT '1',
  `type` enum('public','bank','religious','cultural','observance') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'public',
  `surge_multiplier` decimal(3,2) NOT NULL DEFAULT '1.50',
  `shifts_restricted` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `public_holidays_unique` (`country_code`,`region_code`,`date`,`name`),
  KEY `public_holidays_country_code_date_index` (`country_code`,`date`),
  KEY `public_holidays_date_index` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `public_holidays`
--

LOCK TABLES `public_holidays` WRITE;
/*!40000 ALTER TABLE `public_holidays` DISABLE KEYS */;
/*!40000 ALTER TABLE `public_holidays` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `push_notification_logs`
--

DROP TABLE IF EXISTS `push_notification_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `push_notification_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `token_id` bigint unsigned DEFAULT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` json DEFAULT NULL,
  `platform` enum('fcm','apns','web') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','sent','delivered','failed','clicked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `message_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `clicked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `push_notification_logs_token_id_foreign` (`token_id`),
  KEY `push_notification_logs_user_id_index` (`user_id`),
  KEY `push_notification_logs_status_index` (`status`),
  KEY `push_notification_logs_message_id_index` (`message_id`),
  KEY `push_notification_logs_created_at_index` (`created_at`),
  CONSTRAINT `push_notification_logs_token_id_foreign` FOREIGN KEY (`token_id`) REFERENCES `push_notification_tokens` (`id`) ON DELETE SET NULL,
  CONSTRAINT `push_notification_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `push_notification_logs`
--

LOCK TABLES `push_notification_logs` WRITE;
/*!40000 ALTER TABLE `push_notification_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `push_notification_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `push_notification_tokens`
--

DROP TABLE IF EXISTS `push_notification_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `push_notification_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `platform` enum('fcm','apns','web') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fcm',
  `device_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_model` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `app_version` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `push_notification_tokens_user_id_token_unique` (`user_id`,`token`),
  KEY `push_notification_tokens_token_index` (`token`),
  KEY `push_notification_tokens_user_id_is_active_index` (`user_id`,`is_active`),
  KEY `push_notification_tokens_platform_index` (`platform`),
  CONSTRAINT `push_notification_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `push_notification_tokens`
--

LOCK TABLES `push_notification_tokens` WRITE;
/*!40000 ALTER TABLE `push_notification_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `push_notification_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ratings`
--

DROP TABLE IF EXISTS `ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ratings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shift_assignment_id` bigint unsigned NOT NULL,
  `rater_id` bigint unsigned NOT NULL,
  `rated_id` bigint unsigned NOT NULL,
  `rater_type` enum('worker','business') COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` int unsigned NOT NULL,
  `punctuality_rating` tinyint unsigned DEFAULT NULL,
  `quality_rating` tinyint unsigned DEFAULT NULL,
  `professionalism_rating` tinyint unsigned DEFAULT NULL,
  `reliability_rating` tinyint unsigned DEFAULT NULL,
  `communication_rating` tinyint unsigned DEFAULT NULL,
  `payment_reliability_rating` tinyint unsigned DEFAULT NULL,
  `weighted_score` decimal(3,2) DEFAULT NULL,
  `is_flagged` tinyint(1) NOT NULL DEFAULT '0',
  `flag_reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flagged_at` timestamp NULL DEFAULT NULL,
  `review_text` text COLLATE utf8mb4_unicode_ci,
  `categories` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ratings_shift_assignment_id_rater_id_unique` (`shift_assignment_id`,`rater_id`),
  KEY `ratings_shift_assignment_id_index` (`shift_assignment_id`),
  KEY `ratings_rater_id_index` (`rater_id`),
  KEY `ratings_rated_id_index` (`rated_id`),
  KEY `ratings_rated_id_rating_index` (`rated_id`,`rating`),
  KEY `ratings_created_at_index` (`created_at`),
  KEY `ratings_weighted_score_index` (`weighted_score`),
  KEY `ratings_is_flagged_index` (`is_flagged`),
  CONSTRAINT `ratings_rated_id_foreign` FOREIGN KEY (`rated_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ratings_rater_id_foreign` FOREIGN KEY (`rater_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ratings_shift_assignment_id_foreign` FOREIGN KEY (`shift_assignment_id`) REFERENCES `shift_assignments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ratings`
--

LOCK TABLES `ratings` WRITE;
/*!40000 ALTER TABLE `ratings` DISABLE KEYS */;
/*!40000 ALTER TABLE `ratings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `referral_codes`
--

DROP TABLE IF EXISTS `referral_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `referral_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'worker',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `max_uses` int DEFAULT NULL,
  `uses_count` int NOT NULL DEFAULT '0',
  `expires_at` timestamp NULL DEFAULT NULL,
  `referrer_reward_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `referrer_reward_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cash',
  `referee_reward_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `referee_reward_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cash',
  `referee_shifts_required` int NOT NULL DEFAULT '1',
  `referee_days_required` int DEFAULT NULL,
  `campaign_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `campaign_source` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_rewards_paid` decimal(12,2) NOT NULL DEFAULT '0.00',
  `successful_conversions` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `referral_codes_code_unique` (`code`),
  KEY `referral_codes_code_index` (`code`),
  KEY `referral_codes_is_active_index` (`is_active`),
  KEY `referral_codes_expires_at_index` (`expires_at`),
  KEY `referral_codes_user_id_type_index` (`user_id`,`type`),
  CONSTRAINT `referral_codes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `referral_codes`
--

LOCK TABLES `referral_codes` WRITE;
/*!40000 ALTER TABLE `referral_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `referral_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `referral_usages`
--

DROP TABLE IF EXISTS `referral_usages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `referral_usages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `referral_code_id` bigint unsigned NOT NULL,
  `referrer_id` bigint unsigned NOT NULL,
  `referee_id` bigint unsigned NOT NULL,
  `status` enum('pending','qualified','rewarded','expired','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `referee_shifts_completed` int NOT NULL DEFAULT '0',
  `qualification_met_at` timestamp NULL DEFAULT NULL,
  `referrer_reward_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `referee_reward_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `referrer_reward_paid_at` timestamp NULL DEFAULT NULL,
  `referee_reward_paid_at` timestamp NULL DEFAULT NULL,
  `registration_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registration_user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `referral_usages_referral_code_id_referee_id_unique` (`referral_code_id`,`referee_id`),
  KEY `referral_usages_referrer_id_status_index` (`referrer_id`,`status`),
  KEY `referral_usages_referee_id_status_index` (`referee_id`,`status`),
  KEY `referral_usages_status_index` (`status`),
  CONSTRAINT `referral_usages_referee_id_foreign` FOREIGN KEY (`referee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `referral_usages_referral_code_id_foreign` FOREIGN KEY (`referral_code_id`) REFERENCES `referral_codes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `referral_usages_referrer_id_foreign` FOREIGN KEY (`referrer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `referral_usages`
--

LOCK TABLES `referral_usages` WRITE;
/*!40000 ALTER TABLE `referral_usages` DISABLE KEYS */;
/*!40000 ALTER TABLE `referral_usages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `refunds`
--

DROP TABLE IF EXISTS `refunds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `refunds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `shift_id` bigint unsigned DEFAULT NULL,
  `shift_payment_id` bigint unsigned DEFAULT NULL,
  `processed_by_admin_id` bigint unsigned DEFAULT NULL,
  `refund_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `refund_amount` decimal(10,2) NOT NULL,
  `original_amount` decimal(10,2) NOT NULL,
  `refund_type` enum('auto_cancellation','dispute_resolution','overcharge_correction','penalty_waiver','manual_adjustment','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `refund_reason` enum('cancellation_72hr','business_cancellation','worker_no_show','shift_not_completed','billing_error','overcharge','duplicate_charge','dispute_resolved','penalty_appeal_approved','goodwill','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason_description` text COLLATE utf8mb4_unicode_ci,
  `refund_method` enum('original_payment_method','credit_balance','manual') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'original_payment_method',
  `status` enum('pending','processing','completed','failed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `stripe_refund_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paypal_refund_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_gateway` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `credit_note_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `credit_note_pdf_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `credit_note_generated_at` timestamp NULL DEFAULT NULL,
  `failure_reason` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `initiated_at` timestamp NULL DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `refunds_refund_number_unique` (`refund_number`),
  UNIQUE KEY `refunds_credit_note_number_unique` (`credit_note_number`),
  KEY `refunds_shift_payment_id_foreign` (`shift_payment_id`),
  KEY `refunds_processed_by_admin_id_foreign` (`processed_by_admin_id`),
  KEY `refunds_business_id_index` (`business_id`),
  KEY `refunds_shift_id_index` (`shift_id`),
  KEY `refunds_status_created_at_index` (`status`,`created_at`),
  KEY `refunds_refund_type_status_index` (`refund_type`,`status`),
  KEY `refunds_initiated_at_index` (`initiated_at`),
  KEY `refunds_refund_type_index` (`refund_type`),
  KEY `refunds_refund_reason_index` (`refund_reason`),
  KEY `refunds_status_index` (`status`),
  KEY `refunds_stripe_refund_id_index` (`stripe_refund_id`),
  CONSTRAINT `refunds_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `refunds_processed_by_admin_id_foreign` FOREIGN KEY (`processed_by_admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `refunds_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `refunds_shift_payment_id_foreign` FOREIGN KEY (`shift_payment_id`) REFERENCES `shift_payments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `refunds`
--

LOCK TABLES `refunds` WRITE;
/*!40000 ALTER TABLE `refunds` DISABLE KEYS */;
/*!40000 ALTER TABLE `refunds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `regional_pricing`
--

DROP TABLE IF EXISTS `regional_pricing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `regional_pricing` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `country_code` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `region_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency_code` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ppp_factor` decimal(5,3) NOT NULL DEFAULT '1.000',
  `min_hourly_rate` decimal(8,2) NOT NULL,
  `max_hourly_rate` decimal(8,2) NOT NULL,
  `platform_fee_rate` decimal(5,2) NOT NULL DEFAULT '15.00',
  `worker_fee_rate` decimal(5,2) NOT NULL DEFAULT '5.00',
  `tier_adjustments` json DEFAULT NULL,
  `country_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `regional_pricing_country_code_region_code_unique` (`country_code`,`region_code`),
  KEY `regional_pricing_country_code_index` (`country_code`),
  KEY `regional_pricing_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `regional_pricing`
--

LOCK TABLES `regional_pricing` WRITE;
/*!40000 ALTER TABLE `regional_pricing` DISABLE KEYS */;
/*!40000 ALTER TABLE `regional_pricing` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reliability_score_history`
--

DROP TABLE IF EXISTS `reliability_score_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reliability_score_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `attendance_score` decimal(5,2) NOT NULL COMMENT '40% weight - No-shows and completions',
  `cancellation_score` decimal(5,2) NOT NULL COMMENT '25% weight - Cancellation timing',
  `punctuality_score` decimal(5,2) NOT NULL COMMENT '20% weight - Clock-in timing',
  `responsiveness_score` decimal(5,2) NOT NULL COMMENT '15% weight - Confirmation speed',
  `metrics` json NOT NULL COMMENT 'Raw data used for calculation',
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reliability_score_history_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `reliability_score_history_score_index` (`score`),
  CONSTRAINT `reliability_score_history_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reliability_score_history`
--

LOCK TABLES `reliability_score_history` WRITE;
/*!40000 ALTER TABLE `reliability_score_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `reliability_score_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `right_to_work_verifications`
--

DROP TABLE IF EXISTS `right_to_work_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `right_to_work_verifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `jurisdiction` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `verification_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','documents_submitted','under_review','verified','expired','rejected','additional_docs_required') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `document_combination` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verified_at` date DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `verified_by` bigint unsigned DEFAULT NULL,
  `online_verification_code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `online_verification_reference` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `online_verified_at` timestamp NULL DEFAULT NULL,
  `has_work_restrictions` tinyint(1) NOT NULL DEFAULT '0',
  `work_restrictions` text COLLATE utf8mb4_unicode_ci,
  `work_permit_expiry` date DEFAULT NULL,
  `verification_notes` text COLLATE utf8mb4_unicode_ci,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `verification_method` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `audit_log` json DEFAULT NULL,
  `expiry_reminder_level` tinyint NOT NULL DEFAULT '0',
  `last_reminder_sent_at` timestamp NULL DEFAULT NULL,
  `retention_expires_at` date DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `right_to_work_verifications_verified_by_foreign` (`verified_by`),
  KEY `right_to_work_verifications_user_id_status_index` (`user_id`,`status`),
  KEY `right_to_work_verifications_jurisdiction_status_index` (`jurisdiction`,`status`),
  KEY `right_to_work_verifications_expires_at_status_index` (`expires_at`,`status`),
  KEY `right_to_work_verifications_jurisdiction_index` (`jurisdiction`),
  KEY `right_to_work_verifications_expires_at_index` (`expires_at`),
  CONSTRAINT `right_to_work_verifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `right_to_work_verifications_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `right_to_work_verifications`
--

LOCK TABLES `right_to_work_verifications` WRITE;
/*!40000 ALTER TABLE `right_to_work_verifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `right_to_work_verifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `permission_id` bigint unsigned NOT NULL,
  `granted` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_permissions_role_permission_id_unique` (`role`,`permission_id`),
  KEY `role_permissions_permission_id_foreign` (`permission_id`),
  KEY `role_permissions_role_index` (`role`),
  CONSTRAINT `role_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `team_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roster_invitations`
--

DROP TABLE IF EXISTS `roster_invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roster_invitations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `roster_id` bigint unsigned NOT NULL,
  `worker_id` bigint unsigned NOT NULL,
  `invited_by` bigint unsigned NOT NULL,
  `status` enum('pending','accepted','declined','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `message` text COLLATE utf8mb4_unicode_ci,
  `expires_at` timestamp NOT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `roster_invitations_invited_by_foreign` (`invited_by`),
  KEY `roster_invitations_worker_id_status_index` (`worker_id`,`status`),
  KEY `roster_invitations_roster_id_status_index` (`roster_id`,`status`),
  KEY `roster_invitations_expires_at_index` (`expires_at`),
  CONSTRAINT `roster_invitations_invited_by_foreign` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `roster_invitations_roster_id_foreign` FOREIGN KEY (`roster_id`) REFERENCES `business_rosters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `roster_invitations_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roster_invitations`
--

LOCK TABLES `roster_invitations` WRITE;
/*!40000 ALTER TABLE `roster_invitations` DISABLE KEYS */;
/*!40000 ALTER TABLE `roster_invitations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roster_members`
--

DROP TABLE IF EXISTS `roster_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roster_members` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `roster_id` bigint unsigned NOT NULL,
  `worker_id` bigint unsigned NOT NULL,
  `status` enum('active','inactive','pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `custom_rate` decimal(8,2) DEFAULT NULL,
  `priority` int NOT NULL DEFAULT '0',
  `preferred_positions` json DEFAULT NULL,
  `availability_preferences` json DEFAULT NULL,
  `added_by` bigint unsigned NOT NULL,
  `last_worked_at` timestamp NULL DEFAULT NULL,
  `total_shifts` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roster_members_roster_id_worker_id_unique` (`roster_id`,`worker_id`),
  KEY `roster_members_added_by_foreign` (`added_by`),
  KEY `roster_members_roster_id_status_index` (`roster_id`,`status`),
  KEY `roster_members_worker_id_status_index` (`worker_id`,`status`),
  KEY `roster_members_priority_index` (`priority`),
  CONSTRAINT `roster_members_added_by_foreign` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `roster_members_roster_id_foreign` FOREIGN KEY (`roster_id`) REFERENCES `business_rosters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `roster_members_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roster_members`
--

LOCK TABLES `roster_members` WRITE;
/*!40000 ALTER TABLE `roster_members` DISABLE KEYS */;
/*!40000 ALTER TABLE `roster_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rtw_documents`
--

DROP TABLE IF EXISTS `rtw_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rtw_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `rtw_verification_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `document_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_list` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_number_encrypted` text COLLATE utf8mb4_unicode_ci,
  `issuing_authority_encrypted` text COLLATE utf8mb4_unicode_ci,
  `issuing_country` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `file_path_encrypted` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int unsigned NOT NULL,
  `encryption_key_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','verified','rejected','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `verification_notes` text COLLATE utf8mb4_unicode_ci,
  `verified_by` bigint unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `extracted_data_encrypted` text COLLATE utf8mb4_unicode_ci,
  `ocr_confidence_score` decimal(5,2) DEFAULT NULL,
  `audit_log` json DEFAULT NULL,
  `upload_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `upload_user_agent` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rtw_documents_verified_by_foreign` (`verified_by`),
  KEY `rtwd_user_type_idx` (`user_id`,`document_type`),
  KEY `rtwd_verification_status_idx` (`rtw_verification_id`,`status`),
  KEY `rtw_documents_expiry_date_index` (`expiry_date`),
  CONSTRAINT `rtw_documents_rtw_verification_id_foreign` FOREIGN KEY (`rtw_verification_id`) REFERENCES `right_to_work_verifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rtw_documents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rtw_documents_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rtw_documents`
--

LOCK TABLES `rtw_documents` WRITE;
/*!40000 ALTER TABLE `rtw_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `rtw_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `safety_certifications`
--

DROP TABLE IF EXISTS `safety_certifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `safety_certifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category` enum('food_safety','health','security','industry_specific','general') COLLATE utf8mb4_unicode_ci NOT NULL,
  `issuing_authority` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `validity_months` int DEFAULT NULL,
  `requires_renewal` tinyint(1) NOT NULL DEFAULT '1',
  `applicable_industries` json DEFAULT NULL,
  `applicable_positions` json DEFAULT NULL,
  `is_mandatory` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `safety_certifications_slug_unique` (`slug`),
  KEY `sc_category_idx` (`category`),
  KEY `sc_is_active_idx` (`is_active`),
  KEY `sc_is_mandatory_idx` (`is_mandatory`),
  KEY `sc_category_active_idx` (`category`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `safety_certifications`
--

LOCK TABLES `safety_certifications` WRITE;
/*!40000 ALTER TABLE `safety_certifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `safety_certifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_applications`
--

DROP TABLE IF EXISTS `shift_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_applications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shift_id` bigint unsigned NOT NULL,
  `worker_id` bigint unsigned NOT NULL,
  `status` enum('pending','accepted','rejected','withdrawn') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `match_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `skill_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `proximity_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `reliability_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `rating_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `recency_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `rank_position` int DEFAULT NULL,
  `distance_km` decimal(8,2) DEFAULT NULL,
  `priority_tier` enum('bronze','silver','gold','platinum') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bronze',
  `application_note` text COLLATE utf8mb4_unicode_ci,
  `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notification_sent_at` timestamp NULL DEFAULT NULL,
  `notification_opened_at` timestamp NULL DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `acknowledgment_required_by` timestamp NULL DEFAULT NULL,
  `reminder_sent_at` timestamp NULL DEFAULT NULL,
  `auto_cancelled_at` timestamp NULL DEFAULT NULL,
  `acknowledgment_late` tinyint(1) NOT NULL DEFAULT '0',
  `is_favorited` tinyint(1) NOT NULL DEFAULT '0',
  `is_blocked` tinyint(1) NOT NULL DEFAULT '0',
  `application_source` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mobile_app',
  `device_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `app_version` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `viewed_by_business_at` timestamp NULL DEFAULT NULL,
  `responded_by` bigint unsigned DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shift_applications_shift_id_worker_id_unique` (`shift_id`,`worker_id`),
  KEY `shift_applications_shift_id_index` (`shift_id`),
  KEY `shift_applications_worker_id_index` (`worker_id`),
  KEY `shift_applications_shift_id_status_index` (`shift_id`,`status`),
  KEY `shift_applications_worker_id_status_index` (`worker_id`,`status`),
  KEY `shift_applications_status_index` (`status`),
  KEY `shift_applications_responded_by_foreign` (`responded_by`),
  KEY `shift_applications_match_score_index` (`match_score`),
  KEY `shift_applications_rank_position_index` (`rank_position`),
  KEY `shift_applications_priority_tier_index` (`priority_tier`),
  KEY `shift_applications_is_favorited_index` (`is_favorited`),
  KEY `shift_applications_acknowledged_at_index` (`acknowledged_at`),
  KEY `shift_applications_shift_id_match_score_index` (`shift_id`,`match_score`),
  KEY `idx_applications_pending_date` (`status`,`created_at`),
  KEY `idx_applications_worker_history` (`worker_id`,`created_at`),
  KEY `idx_applications_response` (`status`,`responded_at`),
  CONSTRAINT `shift_applications_responded_by_foreign` FOREIGN KEY (`responded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shift_applications_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shift_applications_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_applications`
--

LOCK TABLES `shift_applications` WRITE;
/*!40000 ALTER TABLE `shift_applications` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_assignments`
--

DROP TABLE IF EXISTS `shift_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shift_id` bigint unsigned NOT NULL,
  `worker_id` bigint unsigned NOT NULL,
  `agency_id` bigint unsigned DEFAULT NULL,
  `agency_commission_rate` decimal(5,2) DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `assigned_by` bigint unsigned NOT NULL,
  `check_in_time` timestamp NULL DEFAULT NULL,
  `actual_clock_in` timestamp NULL DEFAULT NULL,
  `clock_in_lat` decimal(10,8) DEFAULT NULL,
  `clock_in_lng` decimal(11,8) DEFAULT NULL,
  `clock_in_accuracy` int DEFAULT NULL,
  `clock_in_photo_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clock_in_verified` tinyint(1) NOT NULL DEFAULT '0',
  `clock_in_attempts` int NOT NULL DEFAULT '0',
  `clock_in_failure_reason` text COLLATE utf8mb4_unicode_ci,
  `late_minutes` int NOT NULL DEFAULT '0',
  `was_late` tinyint(1) NOT NULL DEFAULT '0',
  `lateness_flagged` tinyint(1) NOT NULL DEFAULT '0',
  `face_match_confidence` decimal(5,2) DEFAULT NULL,
  `liveness_passed` tinyint(1) NOT NULL DEFAULT '0',
  `verification_method` enum('face_recognition','manual_override','supervisor_override') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `breaks` json DEFAULT NULL,
  `total_break_minutes` int NOT NULL DEFAULT '0',
  `mandatory_break_taken` tinyint(1) NOT NULL DEFAULT '0',
  `break_compliance_met` tinyint(1) NOT NULL DEFAULT '1',
  `break_required_by` timestamp NULL DEFAULT NULL,
  `break_warning_sent_at` timestamp NULL DEFAULT NULL,
  `check_out_time` timestamp NULL DEFAULT NULL,
  `actual_clock_out` timestamp NULL DEFAULT NULL,
  `clock_out_lat` decimal(10,8) DEFAULT NULL,
  `clock_out_lng` decimal(11,8) DEFAULT NULL,
  `clock_out_photo_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `completion_notes` text COLLATE utf8mb4_unicode_ci,
  `supervisor_signature` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hours_worked` decimal(5,2) DEFAULT NULL,
  `gross_hours` decimal(8,2) DEFAULT NULL,
  `break_deduction_hours` decimal(8,2) NOT NULL DEFAULT '0.00',
  `net_hours_worked` decimal(8,2) DEFAULT NULL,
  `billable_hours` decimal(8,2) DEFAULT NULL,
  `overtime_hours` decimal(8,2) NOT NULL DEFAULT '0.00',
  `early_departure` tinyint(1) NOT NULL DEFAULT '0',
  `early_departure_minutes` int NOT NULL DEFAULT '0',
  `early_departure_reason` text COLLATE utf8mb4_unicode_ci,
  `overtime_worked` tinyint(1) NOT NULL DEFAULT '0',
  `overtime_approved` tinyint(1) NOT NULL DEFAULT '0',
  `overtime_approved_by` bigint unsigned DEFAULT NULL,
  `overtime_approved_at` timestamp NULL DEFAULT NULL,
  `auto_clocked_out` tinyint(1) NOT NULL DEFAULT '0',
  `auto_clock_out_time` timestamp NULL DEFAULT NULL,
  `auto_clock_out_reason` text COLLATE utf8mb4_unicode_ci,
  `business_adjusted_hours` decimal(8,2) DEFAULT NULL,
  `business_adjustment_reason` text COLLATE utf8mb4_unicode_ci,
  `business_verified_at` timestamp NULL DEFAULT NULL,
  `business_verified_by` bigint unsigned DEFAULT NULL,
  `business_rating` decimal(3,2) DEFAULT NULL,
  `business_feedback` text COLLATE utf8mb4_unicode_ci,
  `status` enum('assigned','checked_in','checked_out','completed','no_show','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'assigned',
  `payment_status` enum('pending','processing','paid','disputed','held') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `worker_pay_amount` decimal(10,2) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `post_shift_survey_sent_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shift_assignments_shift_id_worker_id_unique` (`shift_id`,`worker_id`),
  KEY `shift_assignments_shift_id_index` (`shift_id`),
  KEY `shift_assignments_worker_id_index` (`worker_id`),
  KEY `shift_assignments_shift_id_status_index` (`shift_id`,`status`),
  KEY `shift_assignments_worker_id_status_index` (`worker_id`,`status`),
  KEY `shift_assignments_check_in_time_index` (`check_in_time`),
  KEY `shift_assignments_check_out_time_index` (`check_out_time`),
  KEY `shift_assignments_status_index` (`status`),
  KEY `shift_assignments_agency_id_index` (`agency_id`),
  KEY `shift_assignments_overtime_approved_by_foreign` (`overtime_approved_by`),
  KEY `shift_assignments_business_verified_by_foreign` (`business_verified_by`),
  KEY `shift_assignments_actual_clock_in_index` (`actual_clock_in`),
  KEY `shift_assignments_actual_clock_out_index` (`actual_clock_out`),
  KEY `shift_assignments_payment_status_index` (`payment_status`),
  KEY `shift_assignments_business_verified_at_index` (`business_verified_at`),
  KEY `shift_assignments_was_late_index` (`was_late`),
  KEY `idx_worker_created_at` (`worker_id`,`created_at`),
  KEY `idx_worker_status_created` (`worker_id`,`status`,`created_at`),
  KEY `idx_shift_status_checkin` (`shift_id`,`status`,`check_in_time`),
  KEY `idx_assignments_payment` (`status`,`payment_status`),
  KEY `idx_assignments_checkin` (`status`,`check_in_time`,`shift_id`),
  KEY `idx_assignments_worker_earnings` (`worker_id`,`status`,`created_at`),
  KEY `idx_assignments_assigned_by` (`assigned_by`,`status`),
  CONSTRAINT `shift_assignments_agency_id_foreign` FOREIGN KEY (`agency_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shift_assignments_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shift_assignments_business_verified_by_foreign` FOREIGN KEY (`business_verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shift_assignments_overtime_approved_by_foreign` FOREIGN KEY (`overtime_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shift_assignments_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shift_assignments_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_assignments`
--

LOCK TABLES `shift_assignments` WRITE;
/*!40000 ALTER TABLE `shift_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_attachments`
--

DROP TABLE IF EXISTS `shift_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shift_id` bigint unsigned NOT NULL,
  `uploaded_by` bigint unsigned NOT NULL,
  `file_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shift_attachments_shift_id_index` (`shift_id`),
  KEY `shift_attachments_uploaded_by_index` (`uploaded_by`),
  CONSTRAINT `shift_attachments_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shift_attachments_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_attachments`
--

LOCK TABLES `shift_attachments` WRITE;
/*!40000 ALTER TABLE `shift_attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_audits`
--

DROP TABLE IF EXISTS `shift_audits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_audits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `audit_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shift_id` bigint unsigned NOT NULL,
  `shift_assignment_id` bigint unsigned DEFAULT NULL,
  `auditor_id` bigint unsigned DEFAULT NULL,
  `audit_type` enum('random','complaint','scheduled','mystery_shopper') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `checklist_items` json DEFAULT NULL,
  `overall_score` int DEFAULT NULL,
  `findings` text COLLATE utf8mb4_unicode_ci,
  `recommendations` text COLLATE utf8mb4_unicode_ci,
  `evidence_urls` json DEFAULT NULL,
  `passed` tinyint(1) DEFAULT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shift_audits_audit_number_unique` (`audit_number`),
  KEY `shift_audits_shift_id_foreign` (`shift_id`),
  KEY `shift_audits_shift_assignment_id_foreign` (`shift_assignment_id`),
  KEY `shift_audits_auditor_id_foreign` (`auditor_id`),
  KEY `shift_audits_status_audit_type_index` (`status`,`audit_type`),
  KEY `shift_audits_scheduled_at_index` (`scheduled_at`),
  KEY `shift_audits_completed_at_index` (`completed_at`),
  CONSTRAINT `shift_audits_auditor_id_foreign` FOREIGN KEY (`auditor_id`) REFERENCES `users` (`id`),
  CONSTRAINT `shift_audits_shift_assignment_id_foreign` FOREIGN KEY (`shift_assignment_id`) REFERENCES `shift_assignments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shift_audits_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_audits`
--

LOCK TABLES `shift_audits` WRITE;
/*!40000 ALTER TABLE `shift_audits` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_audits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_certification_requirements`
--

DROP TABLE IF EXISTS `shift_certification_requirements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_certification_requirements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shift_id` bigint unsigned NOT NULL,
  `safety_certification_id` bigint unsigned NOT NULL,
  `is_mandatory` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `scr_shift_cert_unique` (`shift_id`,`safety_certification_id`),
  KEY `scr_shift_idx` (`shift_id`),
  KEY `scr_cert_idx` (`safety_certification_id`),
  KEY `scr_shift_mandatory_idx` (`shift_id`,`is_mandatory`),
  CONSTRAINT `shift_certification_requirements_safety_certification_id_foreign` FOREIGN KEY (`safety_certification_id`) REFERENCES `safety_certifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shift_certification_requirements_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_certification_requirements`
--

LOCK TABLES `shift_certification_requirements` WRITE;
/*!40000 ALTER TABLE `shift_certification_requirements` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_certification_requirements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_invitations`
--

DROP TABLE IF EXISTS `shift_invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_invitations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shift_id` bigint unsigned NOT NULL,
  `worker_id` bigint unsigned NOT NULL,
  `invited_by` bigint unsigned NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','accepted','declined','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `responded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shift_invitations_shift_id_worker_id_unique` (`shift_id`,`worker_id`),
  KEY `shift_invitations_invited_by_foreign` (`invited_by`),
  KEY `shift_invitations_shift_id_index` (`shift_id`),
  KEY `shift_invitations_worker_id_index` (`worker_id`),
  KEY `shift_invitations_worker_id_status_index` (`worker_id`,`status`),
  KEY `shift_invitations_sent_at_index` (`sent_at`),
  KEY `shift_invitations_status_index` (`status`),
  CONSTRAINT `shift_invitations_invited_by_foreign` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shift_invitations_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shift_invitations_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_invitations`
--

LOCK TABLES `shift_invitations` WRITE;
/*!40000 ALTER TABLE `shift_invitations` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_invitations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_notifications`
--

DROP TABLE IF EXISTS `shift_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `shift_id` bigint unsigned DEFAULT NULL,
  `assignment_id` bigint unsigned DEFAULT NULL,
  `type` enum('shift_assigned','shift_cancelled','shift_updated','shift_reminder_2h','shift_reminder_30m','application_received','application_accepted','application_rejected','shift_filled','shift_starting_soon','worker_checked_in','worker_no_show','payment_released','shift_swap_offered','shift_swap_accepted','shift_invitation','worker_cancelled','emergency_alert') COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` json DEFAULT NULL,
  `sent_push` tinyint(1) NOT NULL DEFAULT '0',
  `sent_email` tinyint(1) NOT NULL DEFAULT '0',
  `sent_sms` tinyint(1) NOT NULL DEFAULT '0',
  `read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shift_notifications_assignment_id_foreign` (`assignment_id`),
  KEY `shift_notifications_user_id_index` (`user_id`),
  KEY `shift_notifications_user_id_read_index` (`user_id`,`read`),
  KEY `shift_notifications_shift_id_type_index` (`shift_id`,`type`),
  KEY `shift_notifications_type_index` (`type`),
  CONSTRAINT `shift_notifications_assignment_id_foreign` FOREIGN KEY (`assignment_id`) REFERENCES `shift_assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shift_notifications_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shift_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_notifications`
--

LOCK TABLES `shift_notifications` WRITE;
/*!40000 ALTER TABLE `shift_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_payments`
--

DROP TABLE IF EXISTS `shift_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shift_assignment_id` bigint unsigned NOT NULL,
  `worker_id` bigint unsigned NOT NULL,
  `business_id` bigint unsigned NOT NULL,
  `amount_gross` decimal(10,2) NOT NULL,
  `platform_fee` decimal(10,2) NOT NULL,
  `vat_amount` decimal(10,2) DEFAULT NULL,
  `worker_tax_withheld` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tax_year` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_quarter` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reported_to_tax_authority` tinyint(1) NOT NULL DEFAULT '0',
  `platform_revenue` decimal(10,2) DEFAULT NULL,
  `payment_processor_fee` decimal(10,2) DEFAULT NULL,
  `net_platform_revenue` decimal(10,2) DEFAULT NULL,
  `agency_commission` decimal(10,2) DEFAULT NULL,
  `worker_amount` decimal(10,2) DEFAULT NULL,
  `amount_net` decimal(10,2) NOT NULL,
  `escrow_held_at` timestamp NULL DEFAULT NULL,
  `released_at` timestamp NULL DEFAULT NULL,
  `payout_initiated_at` timestamp NULL DEFAULT NULL,
  `payout_completed_at` timestamp NULL DEFAULT NULL,
  `payout_delay_minutes` int DEFAULT NULL,
  `payout_speed` enum('instant','standard','delayed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'instant',
  `early_payout_requested` tinyint(1) NOT NULL DEFAULT '0',
  `early_payout_fee` decimal(10,2) DEFAULT NULL,
  `requires_manual_review` tinyint(1) NOT NULL DEFAULT '0',
  `manual_review_reason` text COLLATE utf8mb4_unicode_ci,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by_admin_id` bigint unsigned DEFAULT NULL,
  `internal_notes` text COLLATE utf8mb4_unicode_ci,
  `stripe_payment_intent_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_transfer_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending_escrow','in_escrow','released','paid_out','failed','disputed','refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending_escrow',
  `tax_calculation_id` bigint unsigned DEFAULT NULL,
  `tax_withheld` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Total tax withheld from payment',
  `disputed` tinyint(1) NOT NULL DEFAULT '0',
  `dispute_reason` text COLLATE utf8mb4_unicode_ci,
  `disputed_at` timestamp NULL DEFAULT NULL,
  `dispute_filed_by` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dispute_status` enum('pending','under_review','evidence_requested','resolved','closed') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dispute_evidence_url` text COLLATE utf8mb4_unicode_ci,
  `dispute_resolution_notes` text COLLATE utf8mb4_unicode_ci,
  `dispute_adjustment_amount` decimal(10,2) DEFAULT NULL,
  `is_refunded` tinyint(1) NOT NULL DEFAULT '0',
  `refund_amount` decimal(10,2) DEFAULT NULL,
  `refund_reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `refunded_at` timestamp NULL DEFAULT NULL,
  `stripe_refund_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adjustment_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `adjustment_notes` text COLLATE utf8mb4_unicode_ci,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shift_payments_stripe_payment_intent_id_unique` (`stripe_payment_intent_id`),
  UNIQUE KEY `shift_payments_stripe_transfer_id_unique` (`stripe_transfer_id`),
  KEY `shift_payments_shift_assignment_id_index` (`shift_assignment_id`),
  KEY `shift_payments_worker_id_index` (`worker_id`),
  KEY `shift_payments_business_id_index` (`business_id`),
  KEY `shift_payments_status_released_at_index` (`status`,`released_at`),
  KEY `shift_payments_created_at_index` (`created_at`),
  KEY `shift_payments_status_index` (`status`),
  KEY `shift_payments_dispute_status_index` (`dispute_status`),
  KEY `shift_payments_is_refunded_index` (`is_refunded`),
  KEY `shift_payments_tax_year_index` (`tax_year`),
  KEY `shift_payments_requires_manual_review_index` (`requires_manual_review`),
  KEY `shift_payments_payout_speed_index` (`payout_speed`),
  KEY `shift_payments_tax_calculation_id_index` (`tax_calculation_id`),
  KEY `idx_payments_payout_queue` (`status`,`released_at`,`payout_completed_at`),
  KEY `idx_payments_disputes` (`disputed`,`disputed_at`),
  KEY `idx_payments_worker_earnings` (`worker_id`,`status`,`created_at`),
  KEY `idx_payments_business_spending` (`business_id`,`status`,`created_at`),
  KEY `idx_payments_escrow` (`status`,`escrow_held_at`),
  CONSTRAINT `shift_payments_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shift_payments_shift_assignment_id_foreign` FOREIGN KEY (`shift_assignment_id`) REFERENCES `shift_assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shift_payments_tax_calculation_id_foreign` FOREIGN KEY (`tax_calculation_id`) REFERENCES `tax_calculations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shift_payments_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_payments`
--

LOCK TABLES `shift_payments` WRITE;
/*!40000 ALTER TABLE `shift_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_position_assignments`
--

DROP TABLE IF EXISTS `shift_position_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_position_assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shift_position_id` bigint unsigned NOT NULL,
  `shift_assignment_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `position_user_unique` (`shift_position_id`,`user_id`),
  KEY `shift_position_assignments_shift_position_id_index` (`shift_position_id`),
  KEY `shift_position_assignments_shift_assignment_id_index` (`shift_assignment_id`),
  KEY `shift_position_assignments_user_id_index` (`user_id`),
  CONSTRAINT `shift_position_assignments_shift_assignment_id_foreign` FOREIGN KEY (`shift_assignment_id`) REFERENCES `shift_assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shift_position_assignments_shift_position_id_foreign` FOREIGN KEY (`shift_position_id`) REFERENCES `shift_positions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shift_position_assignments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_position_assignments`
--

LOCK TABLES `shift_position_assignments` WRITE;
/*!40000 ALTER TABLE `shift_position_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_position_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_positions`
--

DROP TABLE IF EXISTS `shift_positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_positions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shift_id` bigint unsigned NOT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `hourly_rate` decimal(10,2) NOT NULL,
  `required_workers` int NOT NULL DEFAULT '1',
  `filled_workers` int NOT NULL DEFAULT '0',
  `required_skills` json DEFAULT NULL,
  `required_certifications` json DEFAULT NULL,
  `minimum_experience_hours` int NOT NULL DEFAULT '0',
  `status` enum('open','partially_filled','filled','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shift_positions_shift_id_status_index` (`shift_id`,`status`),
  KEY `shift_positions_status_index` (`status`),
  CONSTRAINT `shift_positions_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_positions`
--

LOCK TABLES `shift_positions` WRITE;
/*!40000 ALTER TABLE `shift_positions` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_positions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_swaps`
--

DROP TABLE IF EXISTS `shift_swaps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_swaps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shift_assignment_id` bigint unsigned NOT NULL,
  `offering_worker_id` bigint unsigned NOT NULL,
  `receiving_worker_id` bigint unsigned DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','approved','rejected','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `business_approval_required` tinyint(1) NOT NULL DEFAULT '1',
  `business_approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shift_swaps_approved_by_foreign` (`approved_by`),
  KEY `shift_swaps_shift_assignment_id_index` (`shift_assignment_id`),
  KEY `shift_swaps_offering_worker_id_index` (`offering_worker_id`),
  KEY `shift_swaps_receiving_worker_id_index` (`receiving_worker_id`),
  KEY `shift_swaps_status_index` (`status`),
  CONSTRAINT `shift_swaps_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shift_swaps_offering_worker_id_foreign` FOREIGN KEY (`offering_worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shift_swaps_receiving_worker_id_foreign` FOREIGN KEY (`receiving_worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shift_swaps_shift_assignment_id_foreign` FOREIGN KEY (`shift_assignment_id`) REFERENCES `shift_assignments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_swaps`
--

LOCK TABLES `shift_swaps` WRITE;
/*!40000 ALTER TABLE `shift_swaps` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_swaps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shift_templates`
--

DROP TABLE IF EXISTS `shift_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shift_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `venue_id` bigint unsigned DEFAULT NULL,
  `template_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shift_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `industry` enum('hospitality','healthcare','retail','events','warehouse','professional') COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_address` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_city` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_state` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_country` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_lat` decimal(10,8) DEFAULT NULL,
  `location_lng` decimal(11,8) DEFAULT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `duration_hours` decimal(5,2) NOT NULL,
  `base_rate` decimal(10,2) NOT NULL,
  `urgency_level` enum('normal','urgent','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `required_workers` int NOT NULL DEFAULT '1',
  `requirements` json DEFAULT NULL,
  `dress_code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parking_info` text COLLATE utf8mb4_unicode_ci,
  `break_info` text COLLATE utf8mb4_unicode_ci,
  `special_instructions` text COLLATE utf8mb4_unicode_ci,
  `auto_renew` tinyint(1) NOT NULL DEFAULT '0',
  `recurrence_pattern` enum('daily','weekly','biweekly','monthly') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recurrence_days` json DEFAULT NULL,
  `recurrence_start_date` date DEFAULT NULL,
  `recurrence_end_date` date DEFAULT NULL,
  `times_used` int NOT NULL DEFAULT '0',
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_via` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `is_from_first_shift` tinyint(1) NOT NULL DEFAULT '0',
  `quick_post_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `default_lead_time_hours` int NOT NULL DEFAULT '24',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_favorite` tinyint(1) NOT NULL DEFAULT '0',
  `is_archived` tinyint(1) NOT NULL DEFAULT '0',
  `successful_fills` int NOT NULL DEFAULT '0',
  `average_fill_time_hours` decimal(8,2) DEFAULT NULL,
  `average_applications` decimal(8,2) DEFAULT NULL,
  `used_suggested_rate` tinyint(1) NOT NULL DEFAULT '0',
  `original_suggested_rate_cents` int DEFAULT NULL,
  `required_skills` json DEFAULT NULL,
  `preferred_skills` json DEFAULT NULL,
  `required_certifications` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shift_templates_business_id_index` (`business_id`),
  KEY `shift_templates_business_id_industry_index` (`business_id`,`industry`),
  KEY `shift_templates_venue_id_index` (`venue_id`),
  KEY `shift_templates_business_id_is_favorite_index` (`business_id`,`is_favorite`),
  KEY `shift_templates_business_id_is_archived_index` (`business_id`,`is_archived`),
  CONSTRAINT `shift_templates_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shift_templates_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shift_templates`
--

LOCK TABLES `shift_templates` WRITE;
/*!40000 ALTER TABLE `shift_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `shift_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shifts`
--

DROP TABLE IF EXISTS `shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shifts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `venue_id` bigint unsigned DEFAULT NULL,
  `posted_by_agent` tinyint(1) NOT NULL DEFAULT '0',
  `agent_id` bigint unsigned DEFAULT NULL,
  `agency_client_id` bigint unsigned DEFAULT NULL,
  `posted_by_agency_id` bigint unsigned DEFAULT NULL,
  `allow_agencies` tinyint(1) NOT NULL DEFAULT '1',
  `template_id` bigint unsigned DEFAULT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `industry` enum('hospitality','healthcare','retail','events','warehouse','professional','logistics','construction','security','cleaning') COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_address` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_city` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_state` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_country` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_lat` decimal(10,8) DEFAULT NULL,
  `location_lng` decimal(11,8) DEFAULT NULL,
  `geofence_radius` int NOT NULL DEFAULT '100',
  `early_clockin_minutes` int NOT NULL DEFAULT '15',
  `late_grace_minutes` int NOT NULL DEFAULT '10',
  `shift_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `start_datetime` datetime DEFAULT NULL,
  `end_datetime` datetime DEFAULT NULL,
  `duration_hours` decimal(5,2) NOT NULL,
  `minimum_shift_duration` decimal(5,2) NOT NULL DEFAULT '3.00',
  `maximum_shift_duration` decimal(5,2) NOT NULL DEFAULT '12.00',
  `required_rest_hours` decimal(5,2) NOT NULL DEFAULT '8.00',
  `base_rate` int DEFAULT NULL,
  `dynamic_rate` int DEFAULT NULL,
  `final_rate` int DEFAULT NULL,
  `minimum_wage` int DEFAULT NULL,
  `base_worker_pay` int DEFAULT NULL,
  `platform_fee_rate` decimal(5,2) NOT NULL DEFAULT '35.00',
  `platform_fee_amount` int DEFAULT NULL,
  `vat_rate` decimal(5,2) NOT NULL DEFAULT '18.00',
  `vat_amount` int DEFAULT NULL,
  `total_business_cost` int DEFAULT NULL,
  `escrow_amount` int DEFAULT NULL,
  `contingency_buffer_rate` decimal(5,2) NOT NULL DEFAULT '5.00',
  `surge_multiplier` decimal(5,2) NOT NULL DEFAULT '1.00',
  `time_surge` decimal(5,2) NOT NULL DEFAULT '1.00',
  `demand_surge` decimal(5,2) NOT NULL DEFAULT '0.00',
  `event_surge` decimal(5,2) NOT NULL DEFAULT '0.00',
  `is_public_holiday` tinyint(1) NOT NULL DEFAULT '0',
  `is_night_shift` tinyint(1) NOT NULL DEFAULT '0',
  `is_weekend` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('draft','open','assigned','in_progress','completed','cancelled','filled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `filled_at` timestamp NULL DEFAULT NULL,
  `urgency_level` enum('normal','urgent','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `requires_overtime_approval` tinyint(1) NOT NULL DEFAULT '0',
  `has_disputes` tinyint(1) NOT NULL DEFAULT '0',
  `auto_approval_eligible` tinyint(1) NOT NULL DEFAULT '1',
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `priority_notification_sent_at` timestamp NULL DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `first_worker_clocked_in_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `last_worker_clocked_out_at` timestamp NULL DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `verified_by` bigint unsigned DEFAULT NULL,
  `auto_approved_at` timestamp NULL DEFAULT NULL,
  `cancelled_by` bigint unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text COLLATE utf8mb4_unicode_ci,
  `cancellation_type` enum('business','worker','admin','system') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancellation_penalty_amount` int DEFAULT NULL,
  `worker_compensation_amount` int DEFAULT NULL,
  `required_workers` int NOT NULL DEFAULT '1',
  `filled_workers` int NOT NULL DEFAULT '0',
  `requirements` json DEFAULT NULL,
  `required_skills` json DEFAULT NULL,
  `required_certifications` json DEFAULT NULL,
  `dress_code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parking_info` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `break_info` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `special_instructions` text COLLATE utf8mb4_unicode_ci,
  `requires_health_declaration` tinyint(1) NOT NULL DEFAULT '0',
  `requires_vaccination` tinyint(1) NOT NULL DEFAULT '0',
  `required_vaccinations` json DEFAULT NULL,
  `ppe_requirements` json DEFAULT NULL,
  `max_capacity` int DEFAULT NULL,
  `health_protocols_notes` text COLLATE utf8mb4_unicode_ci,
  `in_market` tinyint(1) NOT NULL DEFAULT '1',
  `is_demo` tinyint(1) NOT NULL DEFAULT '0',
  `market_posted_at` timestamp NULL DEFAULT NULL,
  `instant_claim_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `market_views` int NOT NULL DEFAULT '0',
  `market_applications` int NOT NULL DEFAULT '0',
  `demo_business_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `application_count` int NOT NULL DEFAULT '0',
  `view_count` int NOT NULL DEFAULT '0',
  `first_application_at` timestamp NULL DEFAULT NULL,
  `last_application_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shifts_venue_id_foreign` (`venue_id`),
  KEY `shifts_agent_id_foreign` (`agent_id`),
  KEY `shifts_template_id_foreign` (`template_id`),
  KEY `shifts_verified_by_foreign` (`verified_by`),
  KEY `shifts_cancelled_by_foreign` (`cancelled_by`),
  KEY `shifts_shift_date_status_index` (`shift_date`,`status`),
  KEY `shifts_industry_status_index` (`industry`,`status`),
  KEY `shifts_location_city_location_state_index` (`location_city`,`location_state`),
  KEY `shifts_is_public_holiday_is_weekend_is_night_shift_index` (`is_public_holiday`,`is_weekend`,`is_night_shift`),
  KEY `idx_market_shifts` (`in_market`,`status`,`shift_date`),
  KEY `shifts_agency_client_id_index` (`agency_client_id`),
  KEY `shifts_allow_agencies_index` (`allow_agencies`),
  KEY `shifts_role_type_index` (`role_type`),
  KEY `shifts_industry_index` (`industry`),
  KEY `shifts_shift_date_index` (`shift_date`),
  KEY `shifts_start_datetime_index` (`start_datetime`),
  KEY `shifts_surge_multiplier_index` (`surge_multiplier`),
  KEY `shifts_confirmed_at_index` (`confirmed_at`),
  KEY `shifts_started_at_index` (`started_at`),
  KEY `shifts_completed_at_index` (`completed_at`),
  KEY `shifts_cancelled_at_index` (`cancelled_at`),
  KEY `shifts_in_market_index` (`in_market`),
  KEY `shifts_is_demo_index` (`is_demo`),
  KEY `idx_business_date_status` (`business_id`,`shift_date`,`status`),
  KEY `idx_business_created_at` (`business_id`,`created_at`),
  KEY `idx_status_date` (`status`,`shift_date`),
  KEY `idx_market_date_status` (`in_market`,`shift_date`,`status`),
  KEY `idx_shifts_business_upcoming` (`business_id`,`status`,`shift_date`),
  KEY `idx_shifts_market_location` (`status`,`shift_date`,`location_state`,`location_city`),
  KEY `idx_shifts_agency_status` (`posted_by_agency_id`,`status`),
  KEY `idx_shifts_urgent` (`status`,`urgency_level`,`shift_date`),
  KEY `idx_shifts_fill_status` (`business_id`,`filled_workers`,`required_workers`),
  CONSTRAINT `shifts_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shifts_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shifts_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shifts_posted_by_agency_id_foreign` FOREIGN KEY (`posted_by_agency_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shifts_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `shift_templates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shifts_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `business_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shifts_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shifts`
--

LOCK TABLES `shifts` WRITE;
/*!40000 ALTER TABLE `shifts` DISABLE KEYS */;
/*!40000 ALTER TABLE `shifts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `skill_certification_requirements`
--

DROP TABLE IF EXISTS `skill_certification_requirements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `skill_certification_requirements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `skill_id` bigint unsigned NOT NULL,
  `certification_type_id` bigint unsigned NOT NULL,
  `requirement_level` enum('required','recommended','optional') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'required',
  `required_in_countries` json DEFAULT NULL,
  `required_in_states` json DEFAULT NULL,
  `required_at_experience_levels` json DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `scr_skill_cert_unique` (`skill_id`,`certification_type_id`),
  KEY `skill_certification_requirements_certification_type_id_foreign` (`certification_type_id`),
  KEY `scr_requirement_level_idx` (`requirement_level`),
  KEY `scr_is_active_idx` (`is_active`),
  CONSTRAINT `skill_certification_requirements_certification_type_id_foreign` FOREIGN KEY (`certification_type_id`) REFERENCES `certification_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `skill_certification_requirements_skill_id_foreign` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `skill_certification_requirements`
--

LOCK TABLES `skill_certification_requirements` WRITE;
/*!40000 ALTER TABLE `skill_certification_requirements` DISABLE KEYS */;
/*!40000 ALTER TABLE `skill_certification_requirements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `skills`
--

DROP TABLE IF EXISTS `skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `skills` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `industry` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subcategory` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `requires_certification` tinyint(1) NOT NULL DEFAULT '0',
  `required_certification_ids` json DEFAULT NULL,
  `icon` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `skills_name_unique` (`name`),
  KEY `skills_name_index` (`name`),
  KEY `skills_industry_index` (`industry`),
  KEY `skills_category_index` (`category`),
  KEY `skills_subcategory_index` (`subcategory`),
  KEY `skills_is_active_index` (`is_active`),
  KEY `skills_requires_certification_index` (`requires_certification`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `skills`
--

LOCK TABLES `skills` WRITE;
/*!40000 ALTER TABLE `skills` DISABLE KEYS */;
/*!40000 ALTER TABLE `skills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_logs`
--

DROP TABLE IF EXISTS `sms_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` enum('sms','whatsapp') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sms',
  `type` enum('otp','shift_reminder','urgent_alert','marketing','transactional') COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `template_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'WhatsApp template ID or SMS template name',
  `template_params` json DEFAULT NULL COMMENT 'Parameters passed to template',
  `provider` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'twilio, vonage, messagebird, meta',
  `provider_message_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'External message ID',
  `status` enum('pending','queued','sent','delivered','failed','read') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `segments` int NOT NULL DEFAULT '1' COMMENT 'SMS segment count',
  `cost` decimal(8,4) DEFAULT NULL COMMENT 'Cost in USD',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `error_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `retry_count` int NOT NULL DEFAULT '0',
  `queued_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sms_logs_provider_message_id_unique` (`provider_message_id`),
  KEY `sms_logs_channel_status_index` (`channel`,`status`),
  KEY `sms_logs_user_id_channel_created_at_index` (`user_id`,`channel`,`created_at`),
  KEY `sms_logs_type_created_at_index` (`type`,`created_at`),
  KEY `sms_logs_sent_at_index` (`sent_at`),
  KEY `sms_logs_phone_number_index` (`phone_number`),
  CONSTRAINT `sms_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_logs`
--

LOCK TABLES `sms_logs` WRITE;
/*!40000 ALTER TABLE `sms_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `sms_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `states`
--

DROP TABLE IF EXISTS `states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `states` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `countries_id` bigint unsigned NOT NULL,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `states_countries_id_code_unique` (`countries_id`,`code`),
  CONSTRAINT `states_countries_id_foreign` FOREIGN KEY (`countries_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `states`
--

LOCK TABLES `states` WRITE;
/*!40000 ALTER TABLE `states` DISABLE KEYS */;
/*!40000 ALTER TABLE `states` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscription_invoices`
--

DROP TABLE IF EXISTS `subscription_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscription_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subscription_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `stripe_invoice_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `tax` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `status` enum('draft','open','paid','void','uncollectible') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `pdf_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hosted_invoice_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `period_start` timestamp NULL DEFAULT NULL,
  `period_end` timestamp NULL DEFAULT NULL,
  `due_date` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `payment_method` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_intent_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `line_items` json DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscription_invoices_stripe_invoice_id_unique` (`stripe_invoice_id`),
  KEY `subscription_invoices_subscription_id_foreign` (`subscription_id`),
  KEY `subscription_invoices_user_id_status_index` (`user_id`,`status`),
  KEY `subscription_invoices_stripe_invoice_id_index` (`stripe_invoice_id`),
  KEY `subscription_invoices_status_index` (`status`),
  KEY `subscription_invoices_paid_at_index` (`paid_at`),
  CONSTRAINT `subscription_invoices_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subscription_invoices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscription_invoices`
--

LOCK TABLES `subscription_invoices` WRITE;
/*!40000 ALTER TABLE `subscription_invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `subscription_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscription_plans`
--

DROP TABLE IF EXISTS `subscription_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscription_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('worker','business','agency') COLLATE utf8mb4_unicode_ci NOT NULL,
  `interval` enum('monthly','quarterly','yearly') COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `stripe_price_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_product_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `features` json NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `trial_days` int NOT NULL DEFAULT '0',
  `is_popular` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `max_users` int DEFAULT NULL COMMENT 'For business/agency plans: max team members',
  `max_shifts_per_month` int DEFAULT NULL COMMENT 'For business plans: shift posting limit',
  `commission_rate` decimal(5,2) DEFAULT NULL COMMENT 'Platform commission percentage',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscription_plans_slug_unique` (`slug`),
  KEY `subscription_plans_type_is_active_index` (`type`,`is_active`),
  KEY `subscription_plans_stripe_price_id_index` (`stripe_price_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscription_plans`
--

LOCK TABLES `subscription_plans` WRITE;
/*!40000 ALTER TABLE `subscription_plans` DISABLE KEYS */;
/*!40000 ALTER TABLE `subscription_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `subscription_plan_id` bigint unsigned NOT NULL,
  `stripe_subscription_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_customer_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','past_due','canceled','trialing','paused','incomplete','incomplete_expired','unpaid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'incomplete',
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `current_period_start` timestamp NULL DEFAULT NULL,
  `current_period_end` timestamp NULL DEFAULT NULL,
  `canceled_at` timestamp NULL DEFAULT NULL,
  `paused_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL COMMENT 'When subscription actually ends if canceled',
  `cancel_at_period_end` tinyint(1) NOT NULL DEFAULT '0',
  `cancellation_reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL COMMENT 'Additional subscription metadata',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscriptions_stripe_subscription_id_unique` (`stripe_subscription_id`),
  KEY `subscriptions_subscription_plan_id_foreign` (`subscription_plan_id`),
  KEY `subscriptions_user_id_status_index` (`user_id`,`status`),
  KEY `subscriptions_stripe_subscription_id_index` (`stripe_subscription_id`),
  KEY `subscriptions_status_index` (`status`),
  KEY `subscriptions_current_period_end_index` (`current_period_end`),
  CONSTRAINT `subscriptions_subscription_plan_id_foreign` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscriptions`
--

LOCK TABLES `subscriptions` WRITE;
/*!40000 ALTER TABLE `subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suggestion_votes`
--

DROP TABLE IF EXISTS `suggestion_votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suggestion_votes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `suggestion_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `vote_type` enum('up','down') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'up',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `suggestion_votes_suggestion_id_user_id_unique` (`suggestion_id`,`user_id`),
  KEY `suggestion_votes_user_id_foreign` (`user_id`),
  CONSTRAINT `suggestion_votes_suggestion_id_foreign` FOREIGN KEY (`suggestion_id`) REFERENCES `improvement_suggestions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `suggestion_votes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suggestion_votes`
--

LOCK TABLES `suggestion_votes` WRITE;
/*!40000 ALTER TABLE `suggestion_votes` DISABLE KEYS */;
/*!40000 ALTER TABLE `suggestion_votes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `surge_events`
--

DROP TABLE IF EXISTS `surge_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `surge_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `region` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `surge_multiplier` decimal(3,2) NOT NULL DEFAULT '1.50',
  `event_type` enum('concert','sports','conference','festival','holiday','weather','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `expected_demand_increase` int DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `surge_events_created_by_foreign` (`created_by`),
  KEY `surge_events_start_date_end_date_region_index` (`start_date`,`end_date`,`region`),
  KEY `surge_events_is_active_start_date_index` (`is_active`,`start_date`),
  CONSTRAINT `surge_events_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `surge_events`
--

LOCK TABLES `surge_events` WRITE;
/*!40000 ALTER TABLE `surge_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `surge_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `survey_responses`
--

DROP TABLE IF EXISTS `survey_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `survey_responses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `survey_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `shift_id` bigint unsigned DEFAULT NULL,
  `answers` json NOT NULL,
  `nps_score` int DEFAULT NULL,
  `feedback_text` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `survey_responses_user_id_foreign` (`user_id`),
  KEY `survey_responses_survey_id_user_id_index` (`survey_id`,`user_id`),
  KEY `survey_responses_survey_id_nps_score_index` (`survey_id`,`nps_score`),
  KEY `survey_responses_shift_id_index` (`shift_id`),
  KEY `survey_responses_created_at_index` (`created_at`),
  CONSTRAINT `survey_responses_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `survey_responses_survey_id_foreign` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE,
  CONSTRAINT `survey_responses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `survey_responses`
--

LOCK TABLES `survey_responses` WRITE;
/*!40000 ALTER TABLE `survey_responses` DISABLE KEYS */;
/*!40000 ALTER TABLE `survey_responses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `surveys`
--

DROP TABLE IF EXISTS `surveys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `surveys` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` enum('nps','csat','post_shift','onboarding','general') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `target_audience` enum('workers','businesses','all') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `questions` json NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `surveys_slug_unique` (`slug`),
  KEY `surveys_type_is_active_index` (`type`,`is_active`),
  KEY `surveys_target_audience_is_active_index` (`target_audience`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `surveys`
--

LOCK TABLES `surveys` WRITE;
/*!40000 ALTER TABLE `surveys` DISABLE KEYS */;
/*!40000 ALTER TABLE `surveys` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suspension_appeals`
--

DROP TABLE IF EXISTS `suspension_appeals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suspension_appeals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `suspension_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `appeal_reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `supporting_evidence` json DEFAULT NULL,
  `status` enum('pending','under_review','approved','denied') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `review_notes` text COLLATE utf8mb4_unicode_ci,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `suspension_appeals_reviewed_by_foreign` (`reviewed_by`),
  KEY `suspension_appeals_suspension_id_status_index` (`suspension_id`,`status`),
  KEY `suspension_appeals_status_created_at_index` (`status`,`created_at`),
  KEY `suspension_appeals_user_id_index` (`user_id`),
  CONSTRAINT `suspension_appeals_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `suspension_appeals_suspension_id_foreign` FOREIGN KEY (`suspension_id`) REFERENCES `worker_suspensions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `suspension_appeals_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suspension_appeals`
--

LOCK TABLES `suspension_appeals` WRITE;
/*!40000 ALTER TABLE `suspension_appeals` DISABLE KEYS */;
/*!40000 ALTER TABLE `suspension_appeals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_health_metrics`
--

DROP TABLE IF EXISTS `system_health_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_health_metrics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `metric_type` enum('api_response_time','shift_fill_rate','payment_success_rate','active_users','queue_depth','error_rate','database_connections','redis_connections','disk_usage','memory_usage','cpu_usage') COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` decimal(15,4) NOT NULL,
  `unit` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `environment` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'production',
  `metadata` json DEFAULT NULL,
  `is_healthy` tinyint(1) NOT NULL DEFAULT '1',
  `threshold_warning` decimal(15,4) DEFAULT NULL,
  `threshold_critical` decimal(15,4) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `system_health_metrics_metric_type_recorded_at_index` (`metric_type`,`recorded_at`),
  KEY `system_health_metrics_is_healthy_index` (`is_healthy`),
  KEY `system_health_metrics_recorded_at_index` (`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_health_metrics`
--

LOCK TABLES `system_health_metrics` WRITE;
/*!40000 ALTER TABLE `system_health_metrics` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_health_metrics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_incidents`
--

DROP TABLE IF EXISTS `system_incidents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_incidents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `severity` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('open','investigating','resolved','closed') COLLATE utf8mb4_unicode_ci NOT NULL,
  `triggered_by_metric_id` bigint unsigned DEFAULT NULL,
  `affected_service` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `detected_at` timestamp NOT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `duration_minutes` int DEFAULT NULL,
  `assigned_to_user_id` bigint unsigned DEFAULT NULL,
  `affected_users` int NOT NULL DEFAULT '0',
  `affected_shifts` int NOT NULL DEFAULT '0',
  `failed_payments` int NOT NULL DEFAULT '0',
  `resolution_notes` text COLLATE utf8mb4_unicode_ci,
  `prevention_steps` text COLLATE utf8mb4_unicode_ci,
  `email_sent` tinyint(1) NOT NULL DEFAULT '0',
  `slack_sent` tinyint(1) NOT NULL DEFAULT '0',
  `last_notification_sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `system_incidents_triggered_by_metric_id_foreign` (`triggered_by_metric_id`),
  KEY `system_incidents_severity_index` (`severity`),
  KEY `system_incidents_status_index` (`status`),
  KEY `system_incidents_detected_at_index` (`detected_at`),
  KEY `system_incidents_assigned_to_user_id_index` (`assigned_to_user_id`),
  CONSTRAINT `system_incidents_assigned_to_user_id_foreign` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `system_incidents_triggered_by_metric_id_foreign` FOREIGN KEY (`triggered_by_metric_id`) REFERENCES `system_health_metrics` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_incidents`
--

LOCK TABLES `system_incidents` WRITE;
/*!40000 ALTER TABLE `system_incidents` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_incidents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_setting_audits`
--

DROP TABLE IF EXISTS `system_setting_audits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_setting_audits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `setting_id` bigint unsigned NOT NULL,
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_value` text COLLATE utf8mb4_unicode_ci,
  `new_value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_by` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `system_setting_audits_setting_id_index` (`setting_id`),
  KEY `system_setting_audits_key_index` (`key`),
  KEY `system_setting_audits_changed_by_index` (`changed_by`),
  KEY `system_setting_audits_created_at_index` (`created_at`),
  CONSTRAINT `system_setting_audits_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `system_setting_audits_setting_id_foreign` FOREIGN KEY (`setting_id`) REFERENCES `system_settings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_setting_audits`
--

LOCK TABLES `system_setting_audits` WRITE;
/*!40000 ALTER TABLE `system_setting_audits` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_setting_audits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `data_type` enum('string','integer','decimal','boolean','json') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `last_modified_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `system_settings_key_unique` (`key`),
  KEY `system_settings_category_index` (`category`),
  KEY `system_settings_is_public_index` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_calculations`
--

DROP TABLE IF EXISTS `tax_calculations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_calculations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `shift_id` bigint unsigned DEFAULT NULL,
  `shift_payment_id` bigint unsigned DEFAULT NULL,
  `tax_jurisdiction_id` bigint unsigned NOT NULL,
  `gross_amount` decimal(10,2) NOT NULL COMMENT 'Gross earnings amount',
  `income_tax` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Income tax withheld',
  `social_security` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Social security contribution',
  `vat_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'VAT amount',
  `withholding` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Withholding tax',
  `net_amount` decimal(10,2) NOT NULL COMMENT 'Net amount after all deductions',
  `breakdown` json DEFAULT NULL COMMENT 'Detailed calculation breakdown',
  `effective_tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Effective tax rate applied',
  `currency_code` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `calculation_type` enum('shift_payment','bonus','adjustment','refund','estimate') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'shift_payment',
  `is_applied` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Whether this calculation was applied',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tax_calculations_shift_payment_id_foreign` (`shift_payment_id`),
  KEY `tax_calculations_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `tax_calculations_shift_id_index` (`shift_id`),
  KEY `tax_calculations_tax_jurisdiction_id_index` (`tax_jurisdiction_id`),
  KEY `tax_calculations_calculation_type_index` (`calculation_type`),
  CONSTRAINT `tax_calculations_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tax_calculations_shift_payment_id_foreign` FOREIGN KEY (`shift_payment_id`) REFERENCES `shift_payments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tax_calculations_tax_jurisdiction_id_foreign` FOREIGN KEY (`tax_jurisdiction_id`) REFERENCES `tax_jurisdictions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tax_calculations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_calculations`
--

LOCK TABLES `tax_calculations` WRITE;
/*!40000 ALTER TABLE `tax_calculations` DISABLE KEYS */;
/*!40000 ALTER TABLE `tax_calculations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_documents`
--

DROP TABLE IF EXISTS `tax_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tax_year` year NOT NULL,
  `document_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `total_earnings` decimal(12,2) NOT NULL DEFAULT '0.00',
  `generated_at` timestamp NULL DEFAULT NULL,
  `downloaded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tax_documents_user_id_tax_year_index` (`user_id`,`tax_year`),
  CONSTRAINT `tax_documents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_documents`
--

LOCK TABLES `tax_documents` WRITE;
/*!40000 ALTER TABLE `tax_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `tax_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_forms`
--

DROP TABLE IF EXISTS `tax_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_forms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `form_type` enum('w9','w8ben','w8bene','p45','p60','self_assessment','tax_declaration') COLLATE utf8mb4_unicode_ci NOT NULL,
  `tax_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Encrypted tax identification number',
  `legal_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `business_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'DBA or trade name if applicable',
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `country_code` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` enum('individual','sole_proprietor','llc','corporation','partnership','trust','estate') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'individual',
  `document_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Uploaded form document',
  `status` enum('pending','verified','rejected','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `submitted_at` timestamp NOT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `verified_by` bigint unsigned DEFAULT NULL,
  `form_data` json DEFAULT NULL COMMENT 'Additional form-specific data',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tax_forms_verified_by_foreign` (`verified_by`),
  KEY `tax_forms_user_id_form_type_index` (`user_id`,`form_type`),
  KEY `tax_forms_user_id_status_index` (`user_id`,`status`),
  KEY `tax_forms_status_index` (`status`),
  KEY `tax_forms_expires_at_index` (`expires_at`),
  CONSTRAINT `tax_forms_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tax_forms_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_forms`
--

LOCK TABLES `tax_forms` WRITE;
/*!40000 ALTER TABLE `tax_forms` DISABLE KEYS */;
/*!40000 ALTER TABLE `tax_forms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_jurisdictions`
--

DROP TABLE IF EXISTS `tax_jurisdictions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_jurisdictions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `country_code` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ISO 3166-1 alpha-2 country code',
  `state_code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'State/province code for sub-national jurisdictions',
  `city` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'City name for city-level tax jurisdictions',
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Human-readable jurisdiction name',
  `income_tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Income tax rate as percentage',
  `social_security_rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Social security/NI rate as percentage',
  `vat_rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'VAT/GST rate as percentage',
  `vat_reverse_charge` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'EU B2B reverse charge mechanism',
  `withholding_rate` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Withholding tax rate as percentage',
  `tax_brackets` json DEFAULT NULL COMMENT 'Progressive tax brackets JSON',
  `tax_free_threshold` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Annual tax-free allowance',
  `requires_w9` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'US W-9 form required',
  `requires_w8ben` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'US W-8BEN form required for non-residents',
  `tax_id_format` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Regex pattern for tax ID validation',
  `tax_id_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Tax ID' COMMENT 'Local name for tax ID (SSN, NI Number, etc.)',
  `currency_code` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD' COMMENT 'Default currency for this jurisdiction',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tax_jurisdictions_unique` (`country_code`,`state_code`,`city`),
  KEY `tax_jurisdictions_country_code_index` (`country_code`),
  KEY `tax_jurisdictions_country_code_state_code_index` (`country_code`,`state_code`),
  KEY `tax_jurisdictions_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_jurisdictions`
--

LOCK TABLES `tax_jurisdictions` WRITE;
/*!40000 ALTER TABLE `tax_jurisdictions` DISABLE KEYS */;
/*!40000 ALTER TABLE `tax_jurisdictions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_rates`
--

DROP TABLE IF EXISTS `tax_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_rates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `country` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `iso_state` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `percentage` decimal(8,4) NOT NULL,
  `status` varchar(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'vat',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tax_rates_country_iso_state_status_index` (`country`,`iso_state`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_rates`
--

LOCK TABLES `tax_rates` WRITE;
/*!40000 ALTER TABLE `tax_rates` DISABLE KEYS */;
/*!40000 ALTER TABLE `tax_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_reports`
--

DROP TABLE IF EXISTS `tax_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `tax_year` int NOT NULL,
  `report_type` enum('1099_nec','1099_k','p60','payment_summary','annual_statement') COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_earnings` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_fees` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_taxes_withheld` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_shifts` int NOT NULL DEFAULT '0',
  `monthly_breakdown` json DEFAULT NULL,
  `jurisdiction_breakdown` json DEFAULT NULL,
  `document_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','generated','sent','acknowledged') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `generated_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tax_reports_user_id_tax_year_report_type_unique` (`user_id`,`tax_year`,`report_type`),
  KEY `tax_reports_tax_year_report_type_index` (`tax_year`,`report_type`),
  KEY `tax_reports_user_id_status_index` (`user_id`,`status`),
  CONSTRAINT `tax_reports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_reports`
--

LOCK TABLES `tax_reports` WRITE;
/*!40000 ALTER TABLE `tax_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `tax_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_withholdings`
--

DROP TABLE IF EXISTS `tax_withholdings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_withholdings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `shift_id` bigint unsigned DEFAULT NULL,
  `tax_jurisdiction_id` bigint unsigned NOT NULL,
  `gross_amount` decimal(10,2) NOT NULL,
  `federal_withholding` decimal(10,2) NOT NULL DEFAULT '0.00',
  `state_withholding` decimal(10,2) NOT NULL DEFAULT '0.00',
  `social_security` decimal(10,2) NOT NULL DEFAULT '0.00',
  `medicare` decimal(10,2) NOT NULL DEFAULT '0.00',
  `other_withholding` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_withheld` decimal(10,2) NOT NULL DEFAULT '0.00',
  `pay_period_start` date NOT NULL,
  `pay_period_end` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tax_withholdings_user_id_pay_period_start_pay_period_end_index` (`user_id`,`pay_period_start`,`pay_period_end`),
  KEY `tax_withholdings_tax_jurisdiction_id_index` (`tax_jurisdiction_id`),
  KEY `tax_withholdings_shift_id_index` (`shift_id`),
  CONSTRAINT `tax_withholdings_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tax_withholdings_tax_jurisdiction_id_foreign` FOREIGN KEY (`tax_jurisdiction_id`) REFERENCES `tax_jurisdictions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tax_withholdings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_withholdings`
--

LOCK TABLES `tax_withholdings` WRITE;
/*!40000 ALTER TABLE `tax_withholdings` DISABLE KEYS */;
/*!40000 ALTER TABLE `tax_withholdings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `team_activities`
--

DROP TABLE IF EXISTS `team_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `team_activities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `team_member_id` bigint unsigned DEFAULT NULL,
  `activity_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metadata` json DEFAULT NULL,
  `ip_address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `venue_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `team_activities_business_id_index` (`business_id`),
  KEY `team_activities_user_id_index` (`user_id`),
  KEY `team_activities_team_member_id_index` (`team_member_id`),
  KEY `team_activities_activity_type_index` (`activity_type`),
  KEY `team_activities_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  KEY `team_activities_venue_id_index` (`venue_id`),
  KEY `team_activities_created_at_index` (`created_at`),
  CONSTRAINT `team_activities_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `team_activities_team_member_id_foreign` FOREIGN KEY (`team_member_id`) REFERENCES `team_members` (`id`) ON DELETE SET NULL,
  CONSTRAINT `team_activities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `team_activities_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `team_activities`
--

LOCK TABLES `team_activities` WRITE;
/*!40000 ALTER TABLE `team_activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `team_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `team_invitations`
--

DROP TABLE IF EXISTS `team_invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `team_invitations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `invited_by` bigint unsigned NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `role` enum('admin','manager','scheduler','viewer') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'viewer',
  `venue_access` json DEFAULT NULL,
  `custom_permissions` json DEFAULT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_hash` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','accepted','declined','expired','revoked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `expires_at` timestamp NOT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `declined_at` timestamp NULL DEFAULT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `revocation_reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resend_count` int NOT NULL DEFAULT '0',
  `last_resent_at` timestamp NULL DEFAULT NULL,
  `accepted_ip` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accepted_user_agent` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_pending_invitation` (`business_id`,`email`,`status`),
  UNIQUE KEY `team_invitations_token_unique` (`token`),
  UNIQUE KEY `team_invitations_token_hash_unique` (`token_hash`),
  KEY `team_invitations_invited_by_foreign` (`invited_by`),
  KEY `team_invitations_business_id_index` (`business_id`),
  KEY `team_invitations_email_index` (`email`),
  KEY `team_invitations_user_id_index` (`user_id`),
  KEY `team_invitations_status_index` (`status`),
  KEY `team_invitations_token_hash_index` (`token_hash`),
  KEY `team_invitations_expires_at_index` (`expires_at`),
  CONSTRAINT `team_invitations_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `team_invitations_invited_by_foreign` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `team_invitations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `team_invitations`
--

LOCK TABLES `team_invitations` WRITE;
/*!40000 ALTER TABLE `team_invitations` DISABLE KEYS */;
/*!40000 ALTER TABLE `team_invitations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `team_members`
--

DROP TABLE IF EXISTS `team_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `team_members` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `invited_by` bigint unsigned DEFAULT NULL,
  `role` enum('owner','administrator','location_manager','scheduler','viewer') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'viewer',
  `venue_access` json DEFAULT NULL,
  `can_post_shifts` tinyint(1) NOT NULL DEFAULT '0',
  `can_edit_shifts` tinyint(1) NOT NULL DEFAULT '0',
  `can_cancel_shifts` tinyint(1) NOT NULL DEFAULT '0',
  `can_approve_applications` tinyint(1) NOT NULL DEFAULT '0',
  `can_manage_workers` tinyint(1) NOT NULL DEFAULT '0',
  `can_view_payments` tinyint(1) NOT NULL DEFAULT '0',
  `can_process_payments` tinyint(1) NOT NULL DEFAULT '0',
  `can_manage_venues` tinyint(1) NOT NULL DEFAULT '0',
  `can_manage_team` tinyint(1) NOT NULL DEFAULT '0',
  `can_view_analytics` tinyint(1) NOT NULL DEFAULT '0',
  `can_manage_settings` tinyint(1) NOT NULL DEFAULT '0',
  `can_manage_billing` tinyint(1) NOT NULL DEFAULT '0',
  `can_view_activity` tinyint(1) NOT NULL DEFAULT '0',
  `can_manage_favorites` tinyint(1) NOT NULL DEFAULT '0',
  `can_manage_integrations` tinyint(1) NOT NULL DEFAULT '0',
  `can_view_reports` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('pending','active','suspended','revoked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `invitation_token` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invitation_id` bigint unsigned DEFAULT NULL,
  `invited_at` timestamp NULL DEFAULT NULL,
  `invitation_expires_at` timestamp NULL DEFAULT NULL,
  `joined_at` timestamp NULL DEFAULT NULL,
  `last_active_at` timestamp NULL DEFAULT NULL,
  `shifts_posted` int NOT NULL DEFAULT '0',
  `shifts_edited` int NOT NULL DEFAULT '0',
  `applications_processed` int NOT NULL DEFAULT '0',
  `workers_approved` int NOT NULL DEFAULT '0',
  `shifts_cancelled` int NOT NULL DEFAULT '0',
  `venues_managed` int NOT NULL DEFAULT '0',
  `login_count` int NOT NULL DEFAULT '0',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `revocation_reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `suspended_by` bigint unsigned DEFAULT NULL,
  `suspended_at` timestamp NULL DEFAULT NULL,
  `suspension_reason` text COLLATE utf8mb4_unicode_ci,
  `requires_2fa` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `team_members_business_id_user_id_unique` (`business_id`,`user_id`),
  UNIQUE KEY `team_members_invitation_token_unique` (`invitation_token`),
  KEY `team_members_invited_by_foreign` (`invited_by`),
  KEY `team_members_business_id_index` (`business_id`),
  KEY `team_members_user_id_index` (`user_id`),
  KEY `team_members_role_index` (`role`),
  KEY `team_members_status_index` (`status`),
  KEY `team_members_invitation_token_index` (`invitation_token`),
  CONSTRAINT `team_members_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `team_members_invited_by_foreign` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `team_members_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `team_members`
--

LOCK TABLES `team_members` WRITE;
/*!40000 ALTER TABLE `team_members` DISABLE KEYS */;
/*!40000 ALTER TABLE `team_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `team_permissions`
--

DROP TABLE IF EXISTS `team_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `team_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `sort_order` int NOT NULL DEFAULT '0',
  `is_sensitive` tinyint(1) NOT NULL DEFAULT '0',
  `default_value` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `team_permissions_slug_unique` (`slug`),
  KEY `team_permissions_category_index` (`category`),
  KEY `team_permissions_sort_order_index` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `team_permissions`
--

LOCK TABLES `team_permissions` WRITE;
/*!40000 ALTER TABLE `team_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `team_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `team_shift_requests`
--

DROP TABLE IF EXISTS `team_shift_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `team_shift_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `team_id` bigint unsigned NOT NULL,
  `shift_id` bigint unsigned NOT NULL,
  `requested_by` bigint unsigned NOT NULL,
  `status` enum('pending','approved','rejected','cancelled','expired','partial') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `members_needed` int NOT NULL DEFAULT '1',
  `members_confirmed` int NOT NULL DEFAULT '0',
  `confirmed_members` json DEFAULT NULL,
  `assigned_members` json DEFAULT NULL,
  `application_message` text COLLATE utf8mb4_unicode_ci,
  `response_message` text COLLATE utf8mb4_unicode_ci,
  `responded_by` bigint unsigned DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `confirmation_deadline` timestamp NULL DEFAULT NULL,
  `priority_score` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `team_shift_unique` (`team_id`,`shift_id`),
  KEY `team_shift_requests_responded_by_foreign` (`responded_by`),
  KEY `team_shift_requests_shift_id_status_index` (`shift_id`,`status`),
  KEY `team_shift_requests_team_id_status_index` (`team_id`,`status`),
  KEY `team_shift_requests_requested_by_index` (`requested_by`),
  KEY `team_shift_requests_status_index` (`status`),
  KEY `team_shift_requests_priority_score_index` (`priority_score`),
  CONSTRAINT `team_shift_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `team_shift_requests_responded_by_foreign` FOREIGN KEY (`responded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `team_shift_requests_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `team_shift_requests_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `worker_teams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `team_shift_requests`
--

LOCK TABLES `team_shift_requests` WRITE;
/*!40000 ALTER TABLE `team_shift_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `team_shift_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `template_sends`
--

DROP TABLE IF EXISTS `template_sends`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `template_sends` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `template_id` bigint unsigned NOT NULL,
  `sender_id` bigint unsigned NOT NULL,
  `recipient_id` bigint unsigned NOT NULL,
  `shift_id` bigint unsigned DEFAULT NULL,
  `channel` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rendered_content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','sent','delivered','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `template_sends_shift_id_foreign` (`shift_id`),
  KEY `template_sends_template_id_status_index` (`template_id`,`status`),
  KEY `template_sends_sender_id_created_at_index` (`sender_id`,`created_at`),
  KEY `template_sends_recipient_id_status_index` (`recipient_id`,`status`),
  CONSTRAINT `template_sends_recipient_id_foreign` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `template_sends_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `template_sends_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `template_sends_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `communication_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `template_sends`
--

LOCK TABLES `template_sends` WRITE;
/*!40000 ALTER TABLE `template_sends` DISABLE KEYS */;
/*!40000 ALTER TABLE `template_sends` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `time_tracking_records`
--

DROP TABLE IF EXISTS `time_tracking_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `time_tracking_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `assignment_id` bigint unsigned NOT NULL,
  `worker_id` bigint unsigned NOT NULL,
  `shift_id` bigint unsigned NOT NULL,
  `type` enum('clock_in','clock_out','break_start','break_end') COLLATE utf8mb4_unicode_ci NOT NULL,
  `verified_at` timestamp NOT NULL,
  `verification_methods` json NOT NULL,
  `verification_results` json NOT NULL,
  `location_data` json NOT NULL,
  `face_confidence` decimal(5,2) DEFAULT NULL,
  `device_info` json NOT NULL,
  `timezone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `calculated_hours` decimal(5,2) DEFAULT NULL,
  `early_departure_minutes` int NOT NULL DEFAULT '0',
  `early_departure_reason` text COLLATE utf8mb4_unicode_ci,
  `overtime_minutes` int NOT NULL DEFAULT '0',
  `manual_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `time_tracking_records_worker_id_verified_at_index` (`worker_id`,`verified_at`),
  KEY `time_tracking_records_shift_id_type_index` (`shift_id`,`type`),
  KEY `time_tracking_records_assignment_id_type_index` (`assignment_id`,`type`),
  KEY `time_tracking_records_type_index` (`type`),
  KEY `time_tracking_records_verified_at_index` (`verified_at`),
  CONSTRAINT `time_tracking_records_assignment_id_foreign` FOREIGN KEY (`assignment_id`) REFERENCES `shift_assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `time_tracking_records_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `time_tracking_records_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `time_tracking_records`
--

LOCK TABLES `time_tracking_records` WRITE;
/*!40000 ALTER TABLE `time_tracking_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `time_tracking_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `translations`
--

DROP TABLE IF EXISTS `translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `translations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `locale` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `group` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `translations_unique` (`locale`,`group`,`key`),
  KEY `translations_locale_group` (`locale`,`group`),
  KEY `translations_locale_index` (`locale`),
  KEY `translations_group_index` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `translations`
--

LOCK TABLES `translations` WRITE;
/*!40000 ALTER TABLE `translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `urgent_shift_requests`
--

DROP TABLE IF EXISTS `urgent_shift_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `urgent_shift_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shift_id` bigint unsigned NOT NULL,
  `business_id` bigint unsigned NOT NULL,
  `urgency_reason` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fill_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `hours_until_shift` int NOT NULL DEFAULT '0',
  `shift_start_time` timestamp NOT NULL,
  `detected_at` timestamp NOT NULL,
  `first_agency_notified_at` timestamp NULL DEFAULT NULL,
  `sla_deadline` timestamp NULL DEFAULT NULL,
  `sla_met` tinyint(1) NOT NULL DEFAULT '0',
  `sla_breached` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('pending','routed','accepted','filled','failed','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `agencies_notified` int NOT NULL DEFAULT '0',
  `agencies_responded` int NOT NULL DEFAULT '0',
  `notified_agency_ids` json DEFAULT NULL,
  `accepted_by_agency_id` bigint unsigned DEFAULT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `response_time_minutes` int DEFAULT NULL,
  `escalated` tinyint(1) NOT NULL DEFAULT '0',
  `escalated_at` timestamp NULL DEFAULT NULL,
  `escalation_notes` text COLLATE utf8mb4_unicode_ci,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolution_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resolution_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `urgent_shift_requests_shift_id_index` (`shift_id`),
  KEY `urgent_shift_requests_business_id_index` (`business_id`),
  KEY `urgent_shift_requests_status_index` (`status`),
  KEY `urgent_shift_requests_detected_at_index` (`detected_at`),
  KEY `urgent_shift_requests_sla_deadline_index` (`sla_deadline`),
  KEY `active_requests_idx` (`status`,`sla_deadline`),
  KEY `urgent_shift_requests_accepted_by_agency_id_foreign` (`accepted_by_agency_id`),
  CONSTRAINT `urgent_shift_requests_accepted_by_agency_id_foreign` FOREIGN KEY (`accepted_by_agency_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `urgent_shift_requests_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `urgent_shift_requests_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `urgent_shift_requests`
--

LOCK TABLES `urgent_shift_requests` WRITE;
/*!40000 ALTER TABLE `urgent_shift_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `urgent_shift_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_data_residency`
--

DROP TABLE IF EXISTS `user_data_residency`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_data_residency` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `data_region_id` bigint unsigned NOT NULL,
  `detected_country` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_selected` tinyint(1) NOT NULL DEFAULT '0',
  `consent_given_at` timestamp NULL DEFAULT NULL,
  `data_locations` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_data_residency_user_id_unique` (`user_id`),
  KEY `user_data_residency_data_region_id_index` (`data_region_id`),
  KEY `user_data_residency_detected_country_index` (`detected_country`),
  CONSTRAINT `user_data_residency_data_region_id_foreign` FOREIGN KEY (`data_region_id`) REFERENCES `data_regions` (`id`),
  CONSTRAINT `user_data_residency_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_data_residency`
--

LOCK TABLES `user_data_residency` WRITE;
/*!40000 ALTER TABLE `user_data_residency` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_data_residency` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_phone_preferences`
--

DROP TABLE IF EXISTS `user_phone_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_phone_preferences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `country_code` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '+1',
  `whatsapp_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `sms_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `preferred_channel` enum('sms','whatsapp') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sms',
  `verified` tinyint(1) NOT NULL DEFAULT '0',
  `verified_at` timestamp NULL DEFAULT NULL,
  `verification_method` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'sms_code, whatsapp_code, manual',
  `marketing_opt_in` tinyint(1) NOT NULL DEFAULT '0',
  `transactional_opt_in` tinyint(1) NOT NULL DEFAULT '1',
  `urgent_alerts_opt_in` tinyint(1) NOT NULL DEFAULT '1',
  `quiet_hours` json DEFAULT NULL COMMENT 'Do not disturb hours: {start: "22:00", end: "07:00", timezone: "UTC"}',
  `whatsapp_opt_in_message_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Message ID when user opted in',
  `whatsapp_opted_in_at` timestamp NULL DEFAULT NULL,
  `whatsapp_opted_out_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_phone_preferences_user_id_unique` (`user_id`),
  KEY `user_phone_preferences_phone_number_index` (`phone_number`),
  KEY `user_phone_preferences_preferred_channel_verified_index` (`preferred_channel`,`verified`),
  CONSTRAINT `user_phone_preferences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_phone_preferences`
--

LOCK TABLES `user_phone_preferences` WRITE;
/*!40000 ALTER TABLE `user_phone_preferences` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_phone_preferences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_risk_scores`
--

DROP TABLE IF EXISTS `user_risk_scores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_risk_scores` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `risk_score` int NOT NULL DEFAULT '0',
  `risk_level` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'low',
  `score_factors` json DEFAULT NULL,
  `last_calculated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_risk_scores_user_id_unique` (`user_id`),
  KEY `user_risk_scores_risk_score_index` (`risk_score`),
  KEY `user_risk_scores_risk_level_index` (`risk_level`),
  CONSTRAINT `user_risk_scores_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_risk_scores`
--

LOCK TABLES `user_risk_scores` WRITE;
/*!40000 ALTER TABLE `user_risk_scores` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_risk_scores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locale` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `preferred_currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EUR',
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_country_code` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_verified_at` timestamp NULL DEFAULT NULL,
  `social_provider` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `social_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `social_avatar` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registration_method` enum('email','phone','google','apple','facebook') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email',
  `registration_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registration_user_agent` text COLLATE utf8mb4_unicode_ci,
  `registration_completed_at` timestamp NULL DEFAULT NULL,
  `referred_by_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referred_by_user_id` bigint unsigned DEFAULT NULL,
  `invited_by_agency_id` bigint unsigned DEFAULT NULL,
  `agency_invitation_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `terms_accepted_at` timestamp NULL DEFAULT NULL,
  `privacy_accepted_at` timestamp NULL DEFAULT NULL,
  `marketing_consent` tinyint(1) NOT NULL DEFAULT '0',
  `is_profile_complete` tinyint(1) NOT NULL DEFAULT '0',
  `requires_password_change` tinyint(1) NOT NULL DEFAULT '0',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `dev_expires_at` timestamp NULL DEFAULT NULL,
  `is_dev_account` tinyint(1) NOT NULL DEFAULT '0',
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `two_factor_secret` text COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text COLLATE utf8mb4_unicode_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `username` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('normal','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `status` enum('active','inactive','suspended') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `is_suspended` tinyint(1) NOT NULL DEFAULT '0',
  `locked_until` timestamp NULL DEFAULT NULL,
  `lock_reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `failed_login_attempts` tinyint unsigned NOT NULL DEFAULT '0',
  `last_failed_login_at` timestamp NULL DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `locked_by_admin_id` bigint unsigned DEFAULT NULL,
  `suspended_until` timestamp NULL DEFAULT NULL,
  `suspension_reason` text COLLATE utf8mb4_unicode_ci,
  `suspension_count` int unsigned NOT NULL DEFAULT '0',
  `strike_count` int NOT NULL DEFAULT '0',
  `last_strike_at` timestamp NULL DEFAULT NULL,
  `last_suspended_at` timestamp NULL DEFAULT NULL,
  `mfa_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `user_type` enum('worker','business','agency','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'worker',
  `is_verified_worker` tinyint(1) NOT NULL DEFAULT '0',
  `kyc_verified` tinyint(1) NOT NULL DEFAULT '0',
  `kyc_verified_at` timestamp NULL DEFAULT NULL,
  `kyc_level` enum('none','basic','enhanced','full') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `is_verified_business` tinyint(1) NOT NULL DEFAULT '0',
  `onboarding_step` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `onboarding_completed` tinyint(1) NOT NULL DEFAULT '0',
  `notification_preferences` json DEFAULT NULL,
  `availability_schedule` json DEFAULT NULL,
  `max_commute_distance` int DEFAULT NULL,
  `rating_as_worker` decimal(3,2) NOT NULL DEFAULT '0.00',
  `rating_as_business` decimal(3,2) NOT NULL DEFAULT '0.00',
  `total_shifts_completed` int NOT NULL DEFAULT '0',
  `total_shifts_posted` int NOT NULL DEFAULT '0',
  `reliability_score` decimal(3,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_username_unique` (`username`),
  KEY `users_role_index` (`role`),
  KEY `users_status_index` (`status`),
  KEY `users_user_type_index` (`user_type`),
  KEY `users_onboarding_completed_index` (`onboarding_completed`),
  KEY `users_suspended_until_index` (`suspended_until`),
  KEY `users_referred_by_user_id_foreign` (`referred_by_user_id`),
  KEY `users_invited_by_agency_id_foreign` (`invited_by_agency_id`),
  KEY `users_phone_index` (`phone`),
  KEY `users_social_provider_social_id_index` (`social_provider`,`social_id`),
  KEY `users_referred_by_code_index` (`referred_by_code`),
  KEY `users_registration_method_index` (`registration_method`),
  KEY `users_locked_until_index` (`locked_until`),
  KEY `users_failed_login_attempts_index` (`failed_login_attempts`),
  KEY `users_two_factor_confirmed_at_index` (`two_factor_confirmed_at`),
  KEY `users_kyc_verified_kyc_level_index` (`kyc_verified`,`kyc_level`),
  KEY `users_is_suspended_index` (`is_suspended`),
  KEY `users_locale_index` (`locale`),
  KEY `users_preferred_currency_index` (`preferred_currency`),
  KEY `idx_users_type_status` (`user_type`,`status`),
  KEY `idx_users_verified_worker` (`is_verified_worker`,`user_type`),
  KEY `idx_users_verified_business` (`is_verified_business`,`user_type`),
  CONSTRAINT `users_invited_by_agency_id_foreign` FOREIGN KEY (`invited_by_agency_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_referred_by_user_id_foreign` FOREIGN KEY (`referred_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Test Worker','worker@test.com','en','EUR',NULL,NULL,NULL,NULL,NULL,NULL,'email',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'2025-12-19 18:00:44',NULL,0,'$2y$10$V8DMOkYX4VIi1fj6p1lXYOzKwtTrrob2E8Xp58TZrM1iafYSnmGF6',NULL,NULL,NULL,NULL,'2025-12-19 17:58:55','2025-12-19 17:58:55',NULL,NULL,'','active',0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,0,'worker',0,0,NULL,'none',0,NULL,0,NULL,NULL,NULL,0.00,0.00,0,0,0.00),(2,'Test Business','business@test.com','en','EUR',NULL,NULL,NULL,NULL,NULL,NULL,'email',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'2025-12-19 18:00:44',NULL,0,'$2y$10$LMts/F3buXlpNtvv.jyd0uV/HNLKNaUsgP2eZr.Akzf5ESoDiP5r2',NULL,NULL,NULL,NULL,'2025-12-19 17:58:55','2025-12-19 17:58:55',NULL,NULL,'','active',0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,0,'business',0,0,NULL,'none',0,NULL,0,NULL,NULL,NULL,0.00,0.00,0,0,0.00),(3,'Test Agency','agency@test.com','en','EUR',NULL,NULL,NULL,NULL,NULL,NULL,'email',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'2025-12-19 18:00:44',NULL,0,'$2y$10$QZNgvKV4bb3YwapD8PTBUO1kG3k3UzzmbcOzKU4MKhOqZC.cxLBvK',NULL,NULL,NULL,NULL,'2025-12-19 17:58:55','2025-12-19 17:58:55',NULL,NULL,'','active',0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,0,'agency',0,0,NULL,'none',0,NULL,0,NULL,NULL,NULL,0.00,0.00,0,0,0.00),(4,'Test Admin','admin@test.com','en','EUR',NULL,NULL,NULL,NULL,NULL,NULL,'email',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'2025-12-19 18:00:44',NULL,0,'$2y$10$lxWYTDLmsjnpt3JLkM4mUOcUsZKqjPpsGdmAhOLzRLbX80Ao1vPq.',NULL,NULL,NULL,NULL,'2025-12-19 17:58:55','2025-12-19 17:58:55',NULL,NULL,'admin','active',0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,0,'admin',0,0,NULL,'none',0,NULL,0,NULL,NULL,NULL,0.00,0.00,0,0,0.00);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vaccination_records`
--

DROP TABLE IF EXISTS `vaccination_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vaccination_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `vaccine_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vaccination_date` date DEFAULT NULL,
  `document_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verification_status` enum('pending','verified','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `verified_at` timestamp NULL DEFAULT NULL,
  `verified_by` bigint unsigned DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `lot_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `is_booster` tinyint(1) NOT NULL DEFAULT '0',
  `dose_number` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vaccination_records_verified_by_foreign` (`verified_by`),
  KEY `vaccination_records_user_id_vaccine_type_index` (`user_id`,`vaccine_type`),
  KEY `vaccination_records_verification_status_created_at_index` (`verification_status`,`created_at`),
  KEY `vaccination_records_vaccine_type_index` (`vaccine_type`),
  CONSTRAINT `vaccination_records_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vaccination_records_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vaccination_records`
--

LOCK TABLES `vaccination_records` WRITE;
/*!40000 ALTER TABLE `vaccination_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `vaccination_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `venue_managers`
--

DROP TABLE IF EXISTS `venue_managers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `venue_managers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `venue_id` bigint unsigned NOT NULL,
  `team_member_id` bigint unsigned NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `can_post_shifts` tinyint(1) NOT NULL DEFAULT '1',
  `can_edit_shifts` tinyint(1) NOT NULL DEFAULT '1',
  `can_cancel_shifts` tinyint(1) NOT NULL DEFAULT '0',
  `can_approve_workers` tinyint(1) NOT NULL DEFAULT '1',
  `can_manage_venue_settings` tinyint(1) NOT NULL DEFAULT '0',
  `notify_new_applications` tinyint(1) NOT NULL DEFAULT '1',
  `notify_shift_changes` tinyint(1) NOT NULL DEFAULT '1',
  `notify_worker_checkins` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `venue_managers_venue_id_team_member_id_unique` (`venue_id`,`team_member_id`),
  KEY `venue_managers_venue_id_index` (`venue_id`),
  KEY `venue_managers_team_member_id_index` (`team_member_id`),
  KEY `venue_managers_is_primary_index` (`is_primary`),
  CONSTRAINT `venue_managers_team_member_id_foreign` FOREIGN KEY (`team_member_id`) REFERENCES `team_members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `venue_managers_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venue_managers`
--

LOCK TABLES `venue_managers` WRITE;
/*!40000 ALTER TABLE `venue_managers` DISABLE KEYS */;
/*!40000 ALTER TABLE `venue_managers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `venue_operating_hours`
--

DROP TABLE IF EXISTS `venue_operating_hours`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `venue_operating_hours` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `venue_id` bigint unsigned NOT NULL,
  `day_of_week` tinyint NOT NULL,
  `open_time` time NOT NULL,
  `close_time` time NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '1',
  `is_open` tinyint(1) NOT NULL DEFAULT '1',
  `notes` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `venue_operating_hours_venue_id_day_of_week_index` (`venue_id`,`day_of_week`),
  KEY `venue_operating_hours_is_open_index` (`is_open`),
  CONSTRAINT `venue_operating_hours_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venue_operating_hours`
--

LOCK TABLES `venue_operating_hours` WRITE;
/*!40000 ALTER TABLE `venue_operating_hours` DISABLE KEYS */;
/*!40000 ALTER TABLE `venue_operating_hours` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `venue_safety_flags`
--

DROP TABLE IF EXISTS `venue_safety_flags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `venue_safety_flags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `venue_id` bigint unsigned NOT NULL,
  `reported_by` bigint unsigned NOT NULL,
  `flag_type` enum('harassment','unsafe_conditions','poor_lighting','no_breaks','unpaid_overtime','inadequate_training','equipment_failure','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `evidence_urls` json DEFAULT NULL,
  `status` enum('reported','investigating','resolved','dismissed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reported',
  `assigned_to` bigint unsigned DEFAULT NULL,
  `resolution_notes` text COLLATE utf8mb4_unicode_ci,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `business_notified` tinyint(1) NOT NULL DEFAULT '0',
  `business_response_due` timestamp NULL DEFAULT NULL,
  `business_response` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `venue_safety_flags_venue_id_status_index` (`venue_id`,`status`),
  KEY `venue_safety_flags_venue_id_severity_index` (`venue_id`,`severity`),
  KEY `venue_safety_flags_venue_id_flag_type_index` (`venue_id`,`flag_type`),
  KEY `venue_safety_flags_venue_id_created_at_index` (`venue_id`,`created_at`),
  KEY `venue_safety_flags_reported_by_index` (`reported_by`),
  KEY `venue_safety_flags_assigned_to_index` (`assigned_to`),
  KEY `venue_safety_flags_status_severity_index` (`status`,`severity`),
  CONSTRAINT `venue_safety_flags_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
  CONSTRAINT `venue_safety_flags_reported_by_foreign` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `venue_safety_flags_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venue_safety_flags`
--

LOCK TABLES `venue_safety_flags` WRITE;
/*!40000 ALTER TABLE `venue_safety_flags` DISABLE KEYS */;
/*!40000 ALTER TABLE `venue_safety_flags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `venue_safety_ratings`
--

DROP TABLE IF EXISTS `venue_safety_ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `venue_safety_ratings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `venue_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `shift_id` bigint unsigned DEFAULT NULL,
  `overall_safety` int unsigned NOT NULL,
  `lighting_rating` int unsigned DEFAULT NULL,
  `parking_safety` int unsigned DEFAULT NULL,
  `emergency_exits` int unsigned DEFAULT NULL,
  `staff_support` int unsigned DEFAULT NULL,
  `equipment_condition` int unsigned DEFAULT NULL,
  `safety_concerns` text COLLATE utf8mb4_unicode_ci,
  `positive_notes` text COLLATE utf8mb4_unicode_ci,
  `would_return` tinyint(1) NOT NULL DEFAULT '1',
  `is_anonymous` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `venue_user_shift_unique` (`venue_id`,`user_id`,`shift_id`),
  KEY `venue_safety_ratings_shift_id_foreign` (`shift_id`),
  KEY `venue_safety_ratings_venue_id_overall_safety_index` (`venue_id`,`overall_safety`),
  KEY `venue_safety_ratings_venue_id_created_at_index` (`venue_id`,`created_at`),
  KEY `venue_safety_ratings_user_id_index` (`user_id`),
  CONSTRAINT `venue_safety_ratings_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `venue_safety_ratings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `venue_safety_ratings_venue_id_foreign` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venue_safety_ratings`
--

LOCK TABLES `venue_safety_ratings` WRITE;
/*!40000 ALTER TABLE `venue_safety_ratings` DISABLE KEYS */;
/*!40000 ALTER TABLE `venue_safety_ratings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `venues`
--

DROP TABLE IF EXISTS `venues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `venues` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_profile_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'office',
  `description` text COLLATE utf8mb4_unicode_ci,
  `parking_instructions` text COLLATE utf8mb4_unicode_ci,
  `entrance_instructions` text COLLATE utf8mb4_unicode_ci,
  `checkin_instructions` text COLLATE utf8mb4_unicode_ci,
  `dress_code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `equipment_provided` text COLLATE utf8mb4_unicode_ci,
  `equipment_required` text COLLATE utf8mb4_unicode_ci,
  `address` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line_2` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `postal_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `country` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'US',
  `timezone` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UTC',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `geofence_radius` int NOT NULL DEFAULT '100',
  `geofence_polygon` json DEFAULT NULL,
  `phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_person` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manager_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manager_phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manager_email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `monthly_budget` int NOT NULL DEFAULT '0',
  `default_hourly_rate` int DEFAULT NULL,
  `auto_approve_favorites` tinyint(1) NOT NULL DEFAULT '0',
  `require_checkin_photo` tinyint(1) NOT NULL DEFAULT '0',
  `require_checkout_signature` tinyint(1) NOT NULL DEFAULT '0',
  `gps_accuracy_required` int NOT NULL DEFAULT '50',
  `settings` json DEFAULT NULL,
  `manager_ids` json DEFAULT NULL,
  `image_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_shift_posted_at` timestamp NULL DEFAULT NULL,
  `active_shifts_count` int NOT NULL DEFAULT '0',
  `current_month_spend` int NOT NULL DEFAULT '0',
  `ytd_spend` int NOT NULL DEFAULT '0',
  `total_shifts` int NOT NULL DEFAULT '0',
  `completed_shifts` int NOT NULL DEFAULT '0',
  `cancelled_shifts` int NOT NULL DEFAULT '0',
  `fill_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `average_rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `safety_score` decimal(3,2) DEFAULT NULL,
  `safety_ratings_count` int NOT NULL DEFAULT '0',
  `active_safety_flags` int NOT NULL DEFAULT '0',
  `safety_verified` tinyint(1) NOT NULL DEFAULT '0',
  `last_safety_audit` timestamp NULL DEFAULT NULL,
  `safety_status` enum('good','caution','warning','restricted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'good',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `status` enum('active','inactive','pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `venues_business_profile_id_index` (`business_profile_id`),
  KEY `venues_code_index` (`code`),
  KEY `venues_is_active_index` (`is_active`),
  KEY `venues_latitude_longitude_index` (`latitude`,`longitude`),
  KEY `venues_type_index` (`type`),
  KEY `venues_status_index` (`status`),
  KEY `venues_timezone_index` (`timezone`),
  CONSTRAINT `venues_business_profile_id_foreign` FOREIGN KEY (`business_profile_id`) REFERENCES `business_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venues`
--

LOCK TABLES `venues` WRITE;
/*!40000 ALTER TABLE `venues` DISABLE KEYS */;
/*!40000 ALTER TABLE `venues` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `verification_codes`
--

DROP TABLE IF EXISTS `verification_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `verification_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `identifier` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('email','phone','password_reset','two_factor') COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attempts` int NOT NULL DEFAULT '0',
  `max_attempts` int NOT NULL DEFAULT '5',
  `is_used` tinyint(1) NOT NULL DEFAULT '0',
  `used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `purpose` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `verification_codes_identifier_type_index` (`identifier`,`type`),
  KEY `verification_codes_code_type_index` (`code`,`type`),
  KEY `verification_codes_token_index` (`token`),
  KEY `verification_codes_expires_at_index` (`expires_at`),
  KEY `verification_codes_user_id_type_index` (`user_id`,`type`),
  KEY `verification_codes_ip_address_created_at_index` (`ip_address`,`created_at`),
  CONSTRAINT `verification_codes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `verification_codes`
--

LOCK TABLES `verification_codes` WRITE;
/*!40000 ALTER TABLE `verification_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `verification_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `verification_documents`
--

DROP TABLE IF EXISTS `verification_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `verification_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `identity_verification_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `document_type` enum('passport','driving_license','national_id','residence_permit','visa','tax_id','utility_bill','bank_statement','selfie','liveness_video','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_subtype` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_document_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_file_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `storage_provider` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 's3',
  `storage_path` text COLLATE utf8mb4_unicode_ci,
  `storage_key` text COLLATE utf8mb4_unicode_ci,
  `storage_bucket` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `storage_region` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_filename` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` int DEFAULT NULL,
  `file_hash` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `encryption_algorithm` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'AES-256-GCM',
  `encryption_iv` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','processing','verified','rejected','expired','deleted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `verification_result` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verification_details` json DEFAULT NULL,
  `authenticity_score` decimal(5,4) DEFAULT NULL,
  `quality_score` decimal(5,4) DEFAULT NULL,
  `extracted_data` text COLLATE utf8mb4_unicode_ci,
  `document_issue_date` date DEFAULT NULL,
  `document_expiry_date` date DEFAULT NULL,
  `issuing_country` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issuing_authority` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ocr_results` json DEFAULT NULL,
  `mrz_data` json DEFAULT NULL,
  `fraud_signals` json DEFAULT NULL,
  `is_authentic` tinyint(1) DEFAULT NULL,
  `is_expired` tinyint(1) NOT NULL DEFAULT '0',
  `is_tampered` tinyint(1) NOT NULL DEFAULT '0',
  `retention_expires_at` timestamp NULL DEFAULT NULL,
  `deletion_requested_at` timestamp NULL DEFAULT NULL,
  `deleted_at_provider_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `verification_documents_identity_verification_id_index` (`identity_verification_id`),
  KEY `verification_documents_user_id_index` (`user_id`),
  KEY `verification_documents_document_type_index` (`document_type`),
  KEY `verification_documents_status_index` (`status`),
  KEY `verification_documents_document_expiry_date_index` (`document_expiry_date`),
  KEY `verification_documents_retention_expires_at_index` (`retention_expires_at`),
  CONSTRAINT `verification_documents_identity_verification_id_foreign` FOREIGN KEY (`identity_verification_id`) REFERENCES `identity_verifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `verification_documents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `verification_documents`
--

LOCK TABLES `verification_documents` WRITE;
/*!40000 ALTER TABLE `verification_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `verification_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `verification_queue`
--

DROP TABLE IF EXISTS `verification_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `verification_queue` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `verifiable_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `verifiable_id` bigint unsigned NOT NULL,
  `verification_type` enum('identity','business_license','background_check','certification') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','in_review','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `documents` json DEFAULT NULL,
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `submitted_at` timestamp NOT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `sla_deadline` timestamp NULL DEFAULT NULL,
  `sla_status` enum('on_track','at_risk','breached') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'on_track',
  `sla_warning_sent_at` timestamp NULL DEFAULT NULL,
  `sla_breach_notified_at` timestamp NULL DEFAULT NULL,
  `priority_score` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `verification_queue_verifiable_type_verifiable_id_index` (`verifiable_type`,`verifiable_id`),
  KEY `verification_queue_status_submitted_at_index` (`status`,`submitted_at`),
  KEY `verification_queue_verification_type_index` (`verification_type`),
  KEY `verification_queue_sla_deadline_index` (`sla_deadline`),
  KEY `verification_queue_sla_status_index` (`sla_status`),
  KEY `verification_queue_status_sla_status_sla_deadline_index` (`status`,`sla_status`,`sla_deadline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `verification_queue`
--

LOCK TABLES `verification_queue` WRITE;
/*!40000 ALTER TABLE `verification_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `verification_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `verification_requirements`
--

DROP TABLE IF EXISTS `verification_requirements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `verification_requirements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `jurisdiction` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `requirement_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_required` tinyint(1) NOT NULL DEFAULT '1',
  `business_types` json DEFAULT NULL,
  `industries` json DEFAULT NULL,
  `validation_api` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `validation_rules` json DEFAULT NULL,
  `validity_months` int DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vr_jurisdiction_type_active_idx` (`jurisdiction`,`requirement_type`,`is_active`),
  KEY `vr_document_type_idx` (`document_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `verification_requirements`
--

LOCK TABLES `verification_requirements` WRITE;
/*!40000 ALTER TABLE `verification_requirements` DISABLE KEYS */;
/*!40000 ALTER TABLE `verification_requirements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `volume_discount_tiers`
--

DROP TABLE IF EXISTS `volume_discount_tiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `volume_discount_tiers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `min_shifts_monthly` int NOT NULL DEFAULT '0',
  `max_shifts_monthly` int DEFAULT NULL,
  `platform_fee_percent` decimal(5,2) NOT NULL,
  `min_monthly_spend` decimal(10,2) DEFAULT NULL,
  `max_monthly_spend` decimal(10,2) DEFAULT NULL,
  `benefits` json DEFAULT NULL,
  `badge_color` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `badge_icon` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `volume_discount_tiers_slug_unique` (`slug`),
  KEY `volume_discount_tiers_is_active_index` (`is_active`),
  KEY `volume_discount_tiers_sort_order_index` (`sort_order`),
  KEY `vol_disc_tiers_shifts_idx` (`min_shifts_monthly`,`max_shifts_monthly`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `volume_discount_tiers`
--

LOCK TABLES `volume_discount_tiers` WRITE;
/*!40000 ALTER TABLE `volume_discount_tiers` DISABLE KEYS */;
/*!40000 ALTER TABLE `volume_discount_tiers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webhooks`
--

DROP TABLE IF EXISTS `webhooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhooks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `url` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `secret` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `events` json NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `failure_count` int NOT NULL DEFAULT '0',
  `last_triggered_at` timestamp NULL DEFAULT NULL,
  `last_success_at` timestamp NULL DEFAULT NULL,
  `last_failure_at` timestamp NULL DEFAULT NULL,
  `last_error` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `webhooks_business_id_is_active_index` (`business_id`,`is_active`),
  KEY `webhooks_is_active_failure_count_index` (`is_active`,`failure_count`),
  CONSTRAINT `webhooks_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `webhooks`
--

LOCK TABLES `webhooks` WRITE;
/*!40000 ALTER TABLE `webhooks` DISABLE KEYS */;
/*!40000 ALTER TABLE `webhooks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `whatsapp_templates`
--

DROP TABLE IF EXISTS `whatsapp_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `whatsapp_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `template_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'WhatsApp template ID from Meta',
  `language` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `category` enum('utility','marketing','authentication') COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Template content with {{1}} placeholders',
  `header` json DEFAULT NULL COMMENT 'Header config: {type: text|image|document, content: ...}',
  `buttons` json DEFAULT NULL COMMENT 'Button configurations',
  `footer` json DEFAULT NULL COMMENT 'Footer text',
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `approved_at` timestamp NULL DEFAULT NULL,
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `whatsapp_templates_template_id_unique` (`template_id`),
  KEY `whatsapp_templates_category_status_is_active_index` (`category`,`status`,`is_active`),
  KEY `whatsapp_templates_language_is_active_index` (`language`,`is_active`),
  KEY `whatsapp_templates_name_index` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `whatsapp_templates`
--

LOCK TABLES `whatsapp_templates` WRITE;
/*!40000 ALTER TABLE `whatsapp_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `whatsapp_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `white_label_configs`
--

DROP TABLE IF EXISTS `white_label_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `white_label_configs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `agency_id` bigint unsigned NOT NULL,
  `subdomain` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `custom_domain` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `custom_domain_verified` tinyint(1) NOT NULL DEFAULT '0',
  `brand_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `logo_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `favicon_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `primary_color` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#3B82F6',
  `secondary_color` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#1E40AF',
  `accent_color` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#10B981',
  `theme_config` json DEFAULT NULL,
  `support_email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `support_phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `custom_css` text COLLATE utf8mb4_unicode_ci,
  `custom_js` text COLLATE utf8mb4_unicode_ci,
  `email_templates` json DEFAULT NULL,
  `hide_powered_by` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `white_label_configs_subdomain_unique` (`subdomain`),
  UNIQUE KEY `white_label_configs_custom_domain_unique` (`custom_domain`),
  KEY `white_label_configs_agency_id_foreign` (`agency_id`),
  KEY `white_label_configs_subdomain_index` (`subdomain`),
  KEY `white_label_configs_custom_domain_index` (`custom_domain`),
  KEY `white_label_configs_is_active_index` (`is_active`),
  CONSTRAINT `white_label_configs_agency_id_foreign` FOREIGN KEY (`agency_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `white_label_configs`
--

LOCK TABLES `white_label_configs` WRITE;
/*!40000 ALTER TABLE `white_label_configs` DISABLE KEYS */;
/*!40000 ALTER TABLE `white_label_configs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `white_label_domains`
--

DROP TABLE IF EXISTS `white_label_domains`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `white_label_domains` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `white_label_config_id` bigint unsigned NOT NULL,
  `domain` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `verification_token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `verification_method` enum('dns_txt','dns_cname','file') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'dns_txt',
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `verified_at` timestamp NULL DEFAULT NULL,
  `last_check_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `white_label_domains_white_label_config_id_foreign` (`white_label_config_id`),
  KEY `white_label_domains_domain_index` (`domain`),
  KEY `white_label_domains_is_verified_index` (`is_verified`),
  KEY `white_label_domains_verification_token_index` (`verification_token`),
  CONSTRAINT `white_label_domains_white_label_config_id_foreign` FOREIGN KEY (`white_label_config_id`) REFERENCES `white_label_configs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `white_label_domains`
--

LOCK TABLES `white_label_domains` WRITE;
/*!40000 ALTER TABLE `white_label_domains` DISABLE KEYS */;
/*!40000 ALTER TABLE `white_label_domains` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `withdrawals`
--

DROP TABLE IF EXISTS `withdrawals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `withdrawals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `payout_method_id` bigint unsigned DEFAULT NULL,
  `amount` bigint NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `status` enum('pending','processing','completed','failed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `external_payout_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `failure_reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `withdrawals_payout_method_id_foreign` (`payout_method_id`),
  KEY `withdrawals_user_id_status_index` (`user_id`,`status`),
  KEY `withdrawals_external_payout_id_index` (`external_payout_id`),
  CONSTRAINT `withdrawals_payout_method_id_foreign` FOREIGN KEY (`payout_method_id`) REFERENCES `payout_methods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `withdrawals_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `withdrawals`
--

LOCK TABLES `withdrawals` WRITE;
/*!40000 ALTER TABLE `withdrawals` DISABLE KEYS */;
/*!40000 ALTER TABLE `withdrawals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worker_activation_logs`
--

DROP TABLE IF EXISTS `worker_activation_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `worker_activation_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `status` enum('pending','eligible','activated','suspended','deactivated') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `eligibility_checks` json DEFAULT NULL,
  `all_required_complete` tinyint(1) NOT NULL DEFAULT '0',
  `required_steps_complete` int NOT NULL DEFAULT '0',
  `required_steps_total` int NOT NULL DEFAULT '0',
  `recommended_steps_complete` int NOT NULL DEFAULT '0',
  `recommended_steps_total` int NOT NULL DEFAULT '0',
  `profile_completeness` decimal(5,2) NOT NULL DEFAULT '0.00',
  `skills_count` int NOT NULL DEFAULT '0',
  `certifications_count` int NOT NULL DEFAULT '0',
  `initial_tier` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `initial_reliability_score` decimal(5,2) DEFAULT NULL,
  `referral_code_used` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referred_by_user_id` bigint unsigned DEFAULT NULL,
  `referral_bonus_amount` decimal(10,2) DEFAULT NULL,
  `referral_bonus_processed` tinyint(1) NOT NULL DEFAULT '0',
  `referral_bonus_processed_at` timestamp NULL DEFAULT NULL,
  `activated_at` timestamp NULL DEFAULT NULL,
  `activated_by` bigint unsigned DEFAULT NULL,
  `activation_notes` text COLLATE utf8mb4_unicode_ci,
  `days_to_activation` int DEFAULT NULL,
  `activation_source` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `worker_activation_logs_user_id_unique` (`user_id`),
  KEY `worker_activation_logs_referred_by_user_id_foreign` (`referred_by_user_id`),
  KEY `worker_activation_logs_activated_by_foreign` (`activated_by`),
  KEY `worker_activation_logs_status_index` (`status`),
  KEY `worker_activation_logs_activated_at_index` (`activated_at`),
  KEY `worker_activation_logs_referral_code_used_index` (`referral_code_used`),
  CONSTRAINT `worker_activation_logs_activated_by_foreign` FOREIGN KEY (`activated_by`) REFERENCES `users` (`id`),
  CONSTRAINT `worker_activation_logs_referred_by_user_id_foreign` FOREIGN KEY (`referred_by_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `worker_activation_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_activation_logs`
--

LOCK TABLES `worker_activation_logs` WRITE;
/*!40000 ALTER TABLE `worker_activation_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `worker_activation_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worker_availability_overrides`
--

DROP TABLE IF EXISTS `worker_availability_overrides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `worker_availability_overrides` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `type` enum('available','unavailable','custom') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'custom',
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `is_one_time` tinyint(1) NOT NULL DEFAULT '1',
  `reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `priority` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `worker_availability_overrides_user_id_date_start_time_unique` (`user_id`,`date`,`start_time`),
  KEY `worker_availability_overrides_user_id_date_index` (`user_id`,`date`),
  CONSTRAINT `worker_availability_overrides_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_availability_overrides`
--

LOCK TABLES `worker_availability_overrides` WRITE;
/*!40000 ALTER TABLE `worker_availability_overrides` DISABLE KEYS */;
/*!40000 ALTER TABLE `worker_availability_overrides` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worker_availability_schedules`
--

DROP TABLE IF EXISTS `worker_availability_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `worker_availability_schedules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `worker_id` bigint unsigned NOT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT '1',
  `preferred_shift_types` json DEFAULT NULL,
  `recurrence` enum('weekly','biweekly','monthly') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'weekly',
  `effective_from` date DEFAULT NULL,
  `effective_until` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `worker_availability_schedules_worker_id_index` (`worker_id`),
  KEY `worker_availability_schedules_worker_id_day_of_week_index` (`worker_id`,`day_of_week`),
  CONSTRAINT `worker_availability_schedules_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_availability_schedules`
--

LOCK TABLES `worker_availability_schedules` WRITE;
/*!40000 ALTER TABLE `worker_availability_schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `worker_availability_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worker_badges`
--

DROP TABLE IF EXISTS `worker_badges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `worker_badges` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `worker_id` bigint unsigned NOT NULL,
  `badge_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `badge_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criteria` json DEFAULT NULL,
  `level` int NOT NULL DEFAULT '1',
  `earned_at` timestamp NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `display_on_profile` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `worker_badges_worker_id_index` (`worker_id`),
  KEY `worker_badges_worker_id_badge_type_index` (`worker_id`,`badge_type`),
  KEY `worker_badges_badge_type_level_index` (`badge_type`,`level`),
  CONSTRAINT `worker_badges_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_badges`
--

LOCK TABLES `worker_badges` WRITE;
/*!40000 ALTER TABLE `worker_badges` DISABLE KEYS */;
/*!40000 ALTER TABLE `worker_badges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worker_blackout_dates`
--

DROP TABLE IF EXISTS `worker_blackout_dates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `worker_blackout_dates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `worker_id` bigint unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `type` enum('vacation','personal','medical','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'personal',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `worker_blackout_dates_worker_id_index` (`worker_id`),
  KEY `worker_blackout_dates_worker_id_start_date_end_date_index` (`worker_id`,`start_date`,`end_date`),
  CONSTRAINT `worker_blackout_dates_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_blackout_dates`
--

LOCK TABLES `worker_blackout_dates` WRITE;
/*!40000 ALTER TABLE `worker_blackout_dates` DISABLE KEYS */;
/*!40000 ALTER TABLE `worker_blackout_dates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worker_certifications`
--

DROP TABLE IF EXISTS `worker_certifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `worker_certifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `worker_id` bigint unsigned NOT NULL,
  `certification_id` bigint unsigned DEFAULT NULL,
  `certification_type_id` bigint unsigned DEFAULT NULL,
  `safety_certification_id` bigint unsigned DEFAULT NULL,
  `certification_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `issuing_authority` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issuing_state` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issuing_country` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `certificate_file` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verification_status` enum('pending','verified','rejected','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `verification_method` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verification_attempted_at` timestamp NULL DEFAULT NULL,
  `verification_response` json DEFAULT NULL,
  `extracted_cert_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `extracted_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `extracted_issue_date` date DEFAULT NULL,
  `extracted_expiry_date` date DEFAULT NULL,
  `ocr_confidence_score` decimal(5,2) DEFAULT NULL,
  `document_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT '0',
  `verified_at` timestamp NULL DEFAULT NULL,
  `verified_by` bigint unsigned DEFAULT NULL,
  `verification_notes` text COLLATE utf8mb4_unicode_ci,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `expiry_reminder_sent` tinyint(1) NOT NULL DEFAULT '0',
  `expiry_reminders_sent` int NOT NULL DEFAULT '0',
  `last_reminder_sent_at` timestamp NULL DEFAULT NULL,
  `renewal_in_progress` tinyint(1) NOT NULL DEFAULT '0',
  `renewal_of_certification_id` bigint unsigned DEFAULT NULL,
  `document_storage_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_encryption_key_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_encrypted` tinyint(1) NOT NULL DEFAULT '0',
  `is_primary` tinyint(1) NOT NULL DEFAULT '1',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `worker_certifications_worker_id_index` (`worker_id`),
  KEY `worker_certifications_certification_id_index` (`certification_id`),
  KEY `worker_certifications_worker_id_certification_id_index` (`worker_id`,`certification_id`),
  KEY `worker_certifications_verified_index` (`verified`),
  KEY `worker_certifications_expiry_date_index` (`expiry_date`),
  KEY `worker_certifications_verified_by_foreign` (`verified_by`),
  KEY `worker_certifications_renewal_of_certification_id_foreign` (`renewal_of_certification_id`),
  KEY `wc_cert_type_id_idx` (`certification_type_id`),
  KEY `wc_verification_method_idx` (`verification_method`),
  KEY `wc_renewal_in_progress_idx` (`renewal_in_progress`),
  KEY `wc_is_primary_idx` (`is_primary`),
  KEY `wc_worker_cert_type_idx` (`worker_id`,`certification_type_id`),
  KEY `wc_safety_cert_idx` (`safety_certification_id`),
  CONSTRAINT `worker_certifications_certification_id_foreign` FOREIGN KEY (`certification_id`) REFERENCES `certifications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `worker_certifications_certification_type_id_foreign` FOREIGN KEY (`certification_type_id`) REFERENCES `certification_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `worker_certifications_renewal_of_certification_id_foreign` FOREIGN KEY (`renewal_of_certification_id`) REFERENCES `worker_certifications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `worker_certifications_safety_certification_id_foreign` FOREIGN KEY (`safety_certification_id`) REFERENCES `safety_certifications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `worker_certifications_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `worker_certifications_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_certifications`
--

LOCK TABLES `worker_certifications` WRITE;
/*!40000 ALTER TABLE `worker_certifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `worker_certifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worker_conversions`
--

DROP TABLE IF EXISTS `worker_conversions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `worker_conversions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `worker_id` bigint unsigned NOT NULL,
  `business_id` bigint unsigned NOT NULL,
  `total_hours_worked` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Total hours worked for this business',
  `total_shifts_completed` int NOT NULL DEFAULT '0',
  `conversion_fee_cents` int NOT NULL COMMENT 'Fee in cents',
  `conversion_fee_tier` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '0-200h, 201-400h, 401-600h, 600+h',
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'pending, paid, completed, cancelled',
  `hire_intent_submitted_at` timestamp NULL DEFAULT NULL,
  `hire_intent_notes` text COLLATE utf8mb4_unicode_ci,
  `worker_notified_at` timestamp NULL DEFAULT NULL,
  `worker_accepted` tinyint(1) NOT NULL DEFAULT '0',
  `worker_accepted_at` timestamp NULL DEFAULT NULL,
  `worker_response_notes` text COLLATE utf8mb4_unicode_ci,
  `payment_completed_at` timestamp NULL DEFAULT NULL,
  `payment_method` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_transaction_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conversion_completed_at` timestamp NULL DEFAULT NULL,
  `non_solicitation_expires_at` timestamp NULL DEFAULT NULL COMMENT '6 months from hire date',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Conversion still active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `worker_conversions_status_index` (`status`),
  KEY `worker_conversions_non_solicitation_expires_at_index` (`non_solicitation_expires_at`),
  KEY `worker_conversions_worker_id_business_id_index` (`worker_id`,`business_id`),
  KEY `worker_conversions_worker_id_index` (`worker_id`),
  KEY `worker_conversions_business_id_index` (`business_id`),
  CONSTRAINT `worker_conversions_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `worker_conversions_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_conversions`
--

LOCK TABLES `worker_conversions` WRITE;
/*!40000 ALTER TABLE `worker_conversions` DISABLE KEYS */;
/*!40000 ALTER TABLE `worker_conversions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worker_earnings`
--

DROP TABLE IF EXISTS `worker_earnings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `worker_earnings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `shift_id` bigint unsigned DEFAULT NULL,
  `type` enum('shift_pay','bonus','tip','referral','adjustment','reimbursement') COLLATE utf8mb4_unicode_ci NOT NULL,
  `gross_amount` decimal(10,2) NOT NULL,
  `platform_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tax_withheld` decimal(10,2) NOT NULL DEFAULT '0.00',
  `net_amount` decimal(10,2) NOT NULL,
  `hours_worked` decimal(5,2) DEFAULT NULL,
  `hourly_rate` decimal(10,2) DEFAULT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `status` enum('pending','approved','paid','disputed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `dispute_reason` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `earned_date` date NOT NULL,
  `pay_date` date DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `worker_earnings_shift_id_foreign` (`shift_id`),
  KEY `worker_earnings_user_id_earned_date_index` (`user_id`,`earned_date`),
  KEY `worker_earnings_user_id_status_index` (`user_id`,`status`),
  KEY `worker_earnings_user_id_type_index` (`user_id`,`type`),
  KEY `worker_earnings_earned_date_index` (`earned_date`),
  CONSTRAINT `worker_earnings_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `worker_earnings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_earnings`
--

LOCK TABLES `worker_earnings` WRITE;
/*!40000 ALTER TABLE `worker_earnings` DISABLE KEYS */;
/*!40000 ALTER TABLE `worker_earnings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worker_endorsements`
--

DROP TABLE IF EXISTS `worker_endorsements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `worker_endorsements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `worker_id` bigint unsigned NOT NULL,
  `business_id` bigint unsigned NOT NULL,
  `skill_id` bigint unsigned DEFAULT NULL,
  `shift_id` bigint unsigned DEFAULT NULL COMMENT 'Shift that prompted endorsement',
  `endorsement_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `endorsement_text` text COLLATE utf8mb4_unicode_ci,
  `is_public` tinyint(1) NOT NULL DEFAULT '1',
  `featured` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Featured on worker profile',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_endorsement` (`worker_id`,`business_id`,`skill_id`),
  KEY `worker_endorsements_shift_id_foreign` (`shift_id`),
  KEY `worker_endorsements_worker_id_index` (`worker_id`),
  KEY `worker_endorsements_business_id_index` (`business_id`),
  KEY `worker_endorsements_skill_id_index` (`skill_id`),
  CONSTRAINT `worker_endorsements_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `worker_endorsements_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `worker_endorsements_skill_id_foreign` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE SET NULL,
  CONSTRAINT `worker_endorsements_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_endorsements`
--

LOCK TABLES `worker_endorsements` WRITE;
/*!40000 ALTER TABLE `worker_endorsements` DISABLE KEYS */;
/*!40000 ALTER TABLE `worker_endorsements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worker_exemptions`
--

DROP TABLE IF EXISTS `worker_exemptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `worker_exemptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `labor_law_rule_id` bigint unsigned NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valid_from` date NOT NULL,
  `valid_until` date DEFAULT NULL,
  `status` enum('pending','approved','rejected','expired','revoked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `rejected_by` bigint unsigned DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `worker_acknowledged` tinyint(1) NOT NULL DEFAULT '0',
  `worker_acknowledged_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `worker_rule_exemption_unique` (`user_id`,`labor_law_rule_id`),
  KEY `worker_exemptions_approved_by_foreign` (`approved_by`),
  KEY `worker_exemptions_rejected_by_foreign` (`rejected_by`),
  KEY `worker_exemptions_user_id_index` (`user_id`),
  KEY `worker_exemptions_labor_law_rule_id_index` (`labor_law_rule_id`),
  KEY `worker_exemptions_status_index` (`status`),
  KEY `worker_exemptions_valid_from_index` (`valid_from`),
  KEY `worker_exemptions_valid_until_index` (`valid_until`),
  KEY `worker_exemptions_user_id_status_index` (`user_id`,`status`),
  CONSTRAINT `worker_exemptions_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `worker_exemptions_labor_law_rule_id_foreign` FOREIGN KEY (`labor_law_rule_id`) REFERENCES `labor_law_rules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `worker_exemptions_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `worker_exemptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_exemptions`
--

LOCK TABLES `worker_exemptions` WRITE;
/*!40000 ALTER TABLE `worker_exemptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `worker_exemptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worker_featured_statuses`
--

DROP TABLE IF EXISTS `worker_featured_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `worker_featured_statuses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `worker_id` bigint unsigned NOT NULL,
  `tier` enum('bronze','silver','gold') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bronze',
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `cost_cents` int unsigned NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EUR',
  `payment_reference` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','active','expired','cancelled','refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `worker_featured_statuses_worker_id_status_index` (`worker_id`,`status`),
  KEY `worker_featured_statuses_start_date_end_date_index` (`start_date`,`end_date`),
  KEY `worker_featured_statuses_status_index` (`status`),
  CONSTRAINT `worker_featured_statuses_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_featured_statuses`
--

LOCK TABLES `worker_featured_statuses` WRITE;
/*!40000 ALTER TABLE `worker_featured_statuses` DISABLE KEYS */;
/*!40000 ALTER TABLE `worker_featured_statuses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worker_penalties`
--

DROP TABLE IF EXISTS `worker_penalties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `worker_penalties` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `worker_id` bigint unsigned NOT NULL,
  `shift_id` bigint unsigned DEFAULT NULL,
  `business_id` bigint unsigned DEFAULT NULL,
  `issued_by_admin_id` bigint unsigned DEFAULT NULL,
  `penalty_type` enum('no_show','late_cancellation','misconduct','property_damage','policy_violation','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `penalty_amount` decimal(10,2) NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `evidence_notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','active','appealed','appeal_approved','appeal_rejected','waived','paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `is_paid` tinyint(1) NOT NULL DEFAULT '0',
  `paid_at` timestamp NULL DEFAULT NULL,
  `payment_method` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issued_at` timestamp NULL DEFAULT NULL,
  `due_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `worker_penalties_issued_by_admin_id_foreign` (`issued_by_admin_id`),
  KEY `worker_penalties_worker_id_index` (`worker_id`),
  KEY `worker_penalties_shift_id_index` (`shift_id`),
  KEY `worker_penalties_business_id_index` (`business_id`),
  KEY `worker_penalties_status_created_at_index` (`status`,`created_at`),
  KEY `worker_penalties_worker_id_status_index` (`worker_id`,`status`),
  KEY `worker_penalties_due_date_index` (`due_date`),
  KEY `worker_penalties_penalty_type_index` (`penalty_type`),
  KEY `worker_penalties_status_index` (`status`),
  CONSTRAINT `worker_penalties_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `worker_penalties_issued_by_admin_id_foreign` FOREIGN KEY (`issued_by_admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `worker_penalties_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `worker_penalties_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_penalties`
--

LOCK TABLES `worker_penalties` WRITE;
/*!40000 ALTER TABLE `worker_penalties` DISABLE KEYS */;
/*!40000 ALTER TABLE `worker_penalties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worker_portfolio_items`
--

DROP TABLE IF EXISTS `worker_portfolio_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `worker_portfolio_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `worker_id` bigint unsigned NOT NULL,
  `type` enum('photo','video','document','certification') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'photo',
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `file_path` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `thumbnail_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_filename` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint unsigned NOT NULL DEFAULT '0',
  `display_order` tinyint unsigned NOT NULL DEFAULT '0',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `is_visible` tinyint(1) NOT NULL DEFAULT '1',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `worker_portfolio_items_worker_id_display_order_index` (`worker_id`,`display_order`),
  KEY `worker_portfolio_items_worker_id_is_featured_index` (`worker_id`,`is_featured`),
  KEY `worker_portfolio_items_worker_id_type_index` (`worker_id`,`type`),
  CONSTRAINT `worker_portfolio_items_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_portfolio_items`
--

LOCK TABLES `worker_portfolio_items` WRITE;
/*!40000 ALTER TABLE `worker_portfolio_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `worker_portfolio_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worker_preferences`
--

DROP TABLE IF EXISTS `worker_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `worker_preferences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `max_hours_per_week` int DEFAULT '40',
  `max_shifts_per_day` int DEFAULT '1',
  `min_hours_per_shift` decimal(4,2) DEFAULT '2.00',
  `max_travel_distance` int DEFAULT '25',
  `distance_unit` enum('km','miles') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'km',
  `preferred_shift_types` json DEFAULT NULL,
  `min_hourly_rate` decimal(10,2) DEFAULT NULL,
  `preferred_currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `preferred_industries` json DEFAULT NULL,
  `preferred_roles` json DEFAULT NULL,
  `excluded_businesses` json DEFAULT NULL,
  `notify_new_shifts` tinyint(1) NOT NULL DEFAULT '1',
  `notify_matching_shifts` tinyint(1) NOT NULL DEFAULT '1',
  `notify_urgent_shifts` tinyint(1) NOT NULL DEFAULT '1',
  `advance_notice_hours` int NOT NULL DEFAULT '24',
  `auto_accept_invitations` tinyint(1) NOT NULL DEFAULT '0',
  `auto_accept_recurring` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `worker_preferences_user_id_unique` (`user_id`),
  CONSTRAINT `worker_preferences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_preferences`
--

LOCK TABLES `worker_preferences` WRITE;
/*!40000 ALTER TABLE `worker_preferences` DISABLE KEYS */;
/*!40000 ALTER TABLE `worker_preferences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worker_profile_view_stats`
--

DROP TABLE IF EXISTS `worker_profile_view_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `worker_profile_view_stats` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `worker_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `total_views` int unsigned NOT NULL DEFAULT '0',
  `unique_views` int unsigned NOT NULL DEFAULT '0',
  `business_views` int unsigned NOT NULL DEFAULT '0',
  `agency_views` int unsigned NOT NULL DEFAULT '0',
  `conversions` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `worker_profile_view_stats_worker_id_date_unique` (`worker_id`,`date`),
  KEY `worker_profile_view_stats_worker_id_date_index` (`worker_id`,`date`),
  CONSTRAINT `worker_profile_view_stats_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_profile_view_stats`
--

LOCK TABLES `worker_profile_view_stats` WRITE;
/*!40000 ALTER TABLE `worker_profile_view_stats` DISABLE KEYS */;
/*!40000 ALTER TABLE `worker_profile_view_stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worker_profile_views`
--

DROP TABLE IF EXISTS `worker_profile_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `worker_profile_views` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `worker_id` bigint unsigned NOT NULL,
  `viewer_id` bigint unsigned DEFAULT NULL,
  `viewer_type` enum('business','agency','worker','guest') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'guest',
  `source` enum('search','direct','public_profile','shift_application','referral','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referrer_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `converted_to_application` tinyint(1) NOT NULL DEFAULT '0',
  `converted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `worker_profile_views_viewer_id_foreign` (`viewer_id`),
  KEY `worker_profile_views_worker_id_created_at_index` (`worker_id`,`created_at`),
  KEY `worker_profile_views_worker_id_viewer_type_index` (`worker_id`,`viewer_type`),
  KEY `worker_profile_views_worker_id_source_index` (`worker_id`,`source`),
  KEY `worker_profile_views_worker_id_converted_to_application_index` (`worker_id`,`converted_to_application`),
  CONSTRAINT `worker_profile_views_viewer_id_foreign` FOREIGN KEY (`viewer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `worker_profile_views_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_profile_views`
--

LOCK TABLES `worker_profile_views` WRITE;
/*!40000 ALTER TABLE `worker_profile_views` DISABLE KEYS */;
/*!40000 ALTER TABLE `worker_profile_views` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worker_profiles`
--

DROP TABLE IF EXISTS `worker_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `worker_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `worker_tier_id` bigint unsigned DEFAULT NULL,
  `tier_achieved_at` timestamp NULL DEFAULT NULL,
  `tier_progress` json DEFAULT NULL,
  `lifetime_shifts` int NOT NULL DEFAULT '0',
  `lifetime_hours` decimal(10,2) NOT NULL DEFAULT '0.00',
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `middle_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferred_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `onboarding_completed` tinyint(1) NOT NULL DEFAULT '0',
  `is_activated` tinyint(1) NOT NULL DEFAULT '0',
  `activated_at` timestamp NULL DEFAULT NULL,
  `is_matching_eligible` tinyint(1) NOT NULL DEFAULT '0',
  `matching_eligibility_reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `onboarding_step` int DEFAULT NULL,
  `onboarding_completed_at` timestamp NULL DEFAULT NULL,
  `identity_verified` tinyint(1) NOT NULL DEFAULT '0',
  `identity_verified_at` timestamp NULL DEFAULT NULL,
  `rtw_verified` tinyint(1) NOT NULL DEFAULT '0',
  `rtw_verified_at` timestamp NULL DEFAULT NULL,
  `rtw_document_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rtw_document_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rtw_expiry_date` date DEFAULT NULL,
  `identity_verification_method` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kyc_status` enum('not_started','pending','in_progress','manual_review','approved','rejected','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_started',
  `kyc_level` enum('none','basic','standard','enhanced') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `kyc_expires_at` timestamp NULL DEFAULT NULL,
  `kyc_verification_id` bigint unsigned DEFAULT NULL,
  `verified_first_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verified_last_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verified_date_of_birth` date DEFAULT NULL,
  `verified_nationality` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `age_verified` tinyint(1) NOT NULL DEFAULT '0',
  `age_verified_at` timestamp NULL DEFAULT NULL,
  `minimum_working_age_met` tinyint(1) NOT NULL DEFAULT '0',
  `work_eligibility_status` enum('not_checked','pending','eligible','ineligible','requires_sponsorship') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_checked',
  `work_eligibility_countries` json DEFAULT NULL,
  `subscription_tier` enum('bronze','silver','gold','platinum') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bronze',
  `tier_expires_at` timestamp NULL DEFAULT NULL,
  `tier_upgraded_at` timestamp NULL DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `hourly_rate_min` decimal(10,2) DEFAULT NULL,
  `hourly_rate_max` decimal(10,2) DEFAULT NULL,
  `industries` json DEFAULT NULL,
  `preferred_industries` json DEFAULT NULL,
  `profile_photo_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_photo_verified` tinyint(1) NOT NULL DEFAULT '0',
  `profile_photo_face_detected` tinyint(1) NOT NULL DEFAULT '0',
  `profile_photo_face_confidence` decimal(5,4) DEFAULT NULL,
  `profile_photo_updated_at` timestamp NULL DEFAULT NULL,
  `resume_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `linkedin_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_connect_account_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_account_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_onboarding_complete` tinyint(1) NOT NULL DEFAULT '0',
  `stripe_onboarding_completed_at` timestamp NULL DEFAULT NULL,
  `stripe_charges_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `stripe_payouts_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `stripe_details_submitted` tinyint(1) NOT NULL DEFAULT '0',
  `stripe_requirements_current` json DEFAULT NULL,
  `stripe_requirements_eventually_due` json DEFAULT NULL,
  `stripe_disabled_reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payout_schedule` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'daily',
  `payout_day` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferred_payout_method` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_payout_at` timestamp NULL DEFAULT NULL,
  `last_payout_amount` decimal(10,2) DEFAULT NULL,
  `total_payouts` int NOT NULL DEFAULT '0',
  `lifetime_payout_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `instant_payouts_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `instant_payout_fee_percentage` decimal(5,2) NOT NULL DEFAULT '1.50',
  `tax_info_collected` tinyint(1) NOT NULL DEFAULT '0',
  `tax_form_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_info_submitted_at` timestamp NULL DEFAULT NULL,
  `public_profile_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `public_profile_slug` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `public_profile_enabled_at` timestamp NULL DEFAULT NULL,
  `availability_schedule` json DEFAULT NULL,
  `transportation` enum('car','bike','public_transit','walking') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'public_transit',
  `max_commute_distance` int NOT NULL DEFAULT '10',
  `years_experience` int NOT NULL DEFAULT '0',
  `rating_average` decimal(3,2) NOT NULL DEFAULT '0.00',
  `avg_punctuality` decimal(3,2) DEFAULT NULL,
  `avg_quality` decimal(3,2) DEFAULT NULL,
  `avg_professionalism` decimal(3,2) DEFAULT NULL,
  `avg_reliability` decimal(3,2) DEFAULT NULL,
  `weighted_rating` decimal(3,2) DEFAULT NULL,
  `total_ratings_count` int unsigned NOT NULL DEFAULT '0',
  `total_shifts_completed` int NOT NULL DEFAULT '0',
  `reliability_score` decimal(3,2) NOT NULL DEFAULT '0.00',
  `total_no_shows` int NOT NULL DEFAULT '0',
  `total_cancellations` int NOT NULL DEFAULT '0',
  `total_late_arrivals` int NOT NULL DEFAULT '0',
  `total_early_departures` int NOT NULL DEFAULT '0',
  `total_no_acknowledgments` int NOT NULL DEFAULT '0',
  `average_response_time_minutes` int NOT NULL DEFAULT '0',
  `total_earnings` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_setup_complete` tinyint(1) NOT NULL DEFAULT '0',
  `payment_setup_at` timestamp NULL DEFAULT NULL,
  `pending_earnings` decimal(10,2) NOT NULL DEFAULT '0.00',
  `withdrawn_earnings` decimal(10,2) NOT NULL DEFAULT '0.00',
  `average_hourly_earned` decimal(8,2) DEFAULT NULL,
  `referral_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referred_by` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_referrals` int NOT NULL DEFAULT '0',
  `referral_earnings` decimal(10,2) NOT NULL DEFAULT '0.00',
  `location_city` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_state` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_country` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `geocoded_address` text COLLATE utf8mb4_unicode_ci,
  `geocoded_at` timestamp NULL DEFAULT NULL,
  `timezone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `background_check_status` enum('not_started','pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_started',
  `background_check_date` date DEFAULT NULL,
  `background_check_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_verified` tinyint(1) NOT NULL DEFAULT '0',
  `phone_verified_at` timestamp NULL DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','non_binary','prefer_not_to_say','other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zip_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `skills` json DEFAULT NULL,
  `certifications` json DEFAULT NULL,
  `hourly_rate` decimal(10,2) DEFAULT NULL,
  `rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `completed_shifts` int NOT NULL DEFAULT '0',
  `is_available` tinyint(1) NOT NULL DEFAULT '1',
  `is_complete` tinyint(1) NOT NULL DEFAULT '0',
  `profile_completion_percentage` tinyint NOT NULL DEFAULT '0',
  `profile_sections_completed` json DEFAULT NULL,
  `profile_last_updated_at` timestamp NULL DEFAULT NULL,
  `location_lat` decimal(10,7) DEFAULT NULL,
  `location_lng` decimal(10,7) DEFAULT NULL,
  `preferred_radius` int NOT NULL DEFAULT '25',
  `first_shift_guidance_shown` tinyint(1) NOT NULL DEFAULT '0',
  `first_shift_completed_at` timestamp NULL DEFAULT NULL,
  `profile_photo_status` enum('pending','approved','rejected','none') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `profile_photo_rejected_reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `onboarding_started_at` timestamp NULL DEFAULT NULL,
  `onboarding_last_step_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `worker_profiles_referral_code_unique` (`referral_code`),
  UNIQUE KEY `worker_profiles_public_profile_slug_unique` (`public_profile_slug`),
  KEY `worker_profiles_user_id_index` (`user_id`),
  KEY `worker_profiles_rating_average_index` (`rating_average`),
  KEY `worker_profiles_background_check_status_index` (`background_check_status`),
  KEY `worker_profiles_is_available_index` (`is_available`),
  KEY `worker_profiles_subscription_tier_index` (`subscription_tier`),
  KEY `worker_profiles_onboarding_completed_index` (`onboarding_completed`),
  KEY `worker_profiles_identity_verified_index` (`identity_verified`),
  KEY `worker_profiles_location_city_index` (`location_city`),
  KEY `worker_profiles_referral_code_index` (`referral_code`),
  KEY `worker_profiles_kyc_verification_id_foreign` (`kyc_verification_id`),
  KEY `worker_profiles_kyc_status_index` (`kyc_status`),
  KEY `worker_profiles_kyc_level_index` (`kyc_level`),
  KEY `worker_profiles_kyc_expires_at_index` (`kyc_expires_at`),
  KEY `worker_profiles_age_verified_index` (`age_verified`),
  KEY `worker_profiles_profile_completion_percentage_index` (`profile_completion_percentage`),
  KEY `worker_profiles_stripe_connect_account_id_index` (`stripe_connect_account_id`),
  KEY `worker_profiles_stripe_onboarding_complete_index` (`stripe_onboarding_complete`),
  KEY `worker_profiles_stripe_payouts_enabled_index` (`stripe_payouts_enabled`),
  KEY `worker_profiles_weighted_rating_index` (`weighted_rating`),
  KEY `worker_profiles_avg_punctuality_index` (`avg_punctuality`),
  KEY `worker_profiles_avg_quality_index` (`avg_quality`),
  KEY `worker_profiles_worker_tier_id_index` (`worker_tier_id`),
  KEY `idx_worker_profiles_performance` (`reliability_score`,`rating_average`),
  KEY `idx_worker_profiles_available` (`is_available`,`background_check_status`),
  KEY `idx_worker_profiles_experience` (`years_experience`,`rating_average`),
  CONSTRAINT `worker_profiles_kyc_verification_id_foreign` FOREIGN KEY (`kyc_verification_id`) REFERENCES `identity_verifications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `worker_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `worker_profiles_worker_tier_id_foreign` FOREIGN KEY (`worker_tier_id`) REFERENCES `worker_tiers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_profiles`
--

LOCK TABLES `worker_profiles` WRITE;
/*!40000 ALTER TABLE `worker_profiles` DISABLE KEYS */;
INSERT INTO `worker_profiles` VALUES (1,1,NULL,NULL,NULL,0,0.00,'Test','Worker',NULL,NULL,1,0,NULL,0,NULL,NULL,NULL,0,NULL,0,NULL,NULL,NULL,NULL,NULL,'not_started','none',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,'not_checked',NULL,'bronze',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,0,0,NULL,NULL,NULL,'daily',NULL,NULL,NULL,NULL,0,0.00,0,1.50,0,NULL,NULL,0,NULL,NULL,NULL,'public_transit',10,0,0.00,NULL,NULL,NULL,NULL,NULL,0,0,0.00,0,0,0,0,0,0,0.00,0,NULL,0.00,0.00,NULL,NULL,NULL,0,0.00,NULL,NULL,NULL,NULL,NULL,NULL,'not_started',NULL,NULL,'2025-12-19 17:58:55','2025-12-19 17:58:55',NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0,1,1,0,NULL,NULL,NULL,NULL,25,0,NULL,'none',NULL,NULL,NULL);
/*!40000 ALTER TABLE `worker_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worker_relationships`
--

DROP TABLE IF EXISTS `worker_relationships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `worker_relationships` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `worker_id` bigint unsigned NOT NULL,
  `related_worker_id` bigint unsigned NOT NULL,
  `relationship_type` enum('buddy','preferred','avoided','mentor','mentee') COLLATE utf8mb4_unicode_ci NOT NULL,
  `shifts_together` int NOT NULL DEFAULT '0',
  `compatibility_score` decimal(5,2) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_mutual` tinyint(1) NOT NULL DEFAULT '0',
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','active','declined','removed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `last_calculated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `worker_rel_unique` (`worker_id`,`related_worker_id`,`relationship_type`),
  KEY `worker_relationships_worker_type_idx` (`worker_id`,`relationship_type`),
  KEY `worker_relationships_related_type_idx` (`related_worker_id`,`relationship_type`),
  KEY `worker_relationships_worker_status_idx` (`worker_id`,`status`),
  KEY `worker_relationships_compatibility_score_index` (`compatibility_score`),
  KEY `worker_relationships_shifts_together_index` (`shifts_together`),
  CONSTRAINT `worker_relationships_related_worker_id_foreign` FOREIGN KEY (`related_worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `worker_relationships_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_relationships`
--

LOCK TABLES `worker_relationships` WRITE;
/*!40000 ALTER TABLE `worker_relationships` DISABLE KEYS */;
/*!40000 ALTER TABLE `worker_relationships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worker_skills`
--

DROP TABLE IF EXISTS `worker_skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `worker_skills` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `worker_id` bigint unsigned NOT NULL,
  `skill_id` bigint unsigned NOT NULL,
  `proficiency_level` enum('beginner','intermediate','advanced','expert') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'intermediate',
  `years_experience` int NOT NULL DEFAULT '0',
  `experience_level` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'entry',
  `experience_notes` text COLLATE utf8mb4_unicode_ci,
  `last_used_date` date DEFAULT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT '0',
  `self_assessed` tinyint(1) NOT NULL DEFAULT '1',
  `verified_at` timestamp NULL DEFAULT NULL,
  `verified_by` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `worker_skills_worker_id_skill_id_unique` (`worker_id`,`skill_id`),
  KEY `worker_skills_worker_id_index` (`worker_id`),
  KEY `worker_skills_skill_id_index` (`skill_id`),
  KEY `worker_skills_worker_id_skill_id_index` (`worker_id`,`skill_id`),
  KEY `worker_skills_verified_by_foreign` (`verified_by`),
  KEY `worker_skills_experience_level_index` (`experience_level`),
  KEY `worker_skills_is_active_index` (`is_active`),
  KEY `worker_skills_verified_at_index` (`verified_at`),
  CONSTRAINT `worker_skills_skill_id_foreign` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE,
  CONSTRAINT `worker_skills_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `worker_skills_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_skills`
--

LOCK TABLES `worker_skills` WRITE;
/*!40000 ALTER TABLE `worker_skills` DISABLE KEYS */;
/*!40000 ALTER TABLE `worker_skills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worker_suspensions`
--

DROP TABLE IF EXISTS `worker_suspensions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `worker_suspensions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `type` enum('warning','temporary','indefinite','permanent') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason_category` enum('no_show','late_cancellation','misconduct','policy_violation','fraud','safety','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason_details` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `related_shift_id` bigint unsigned DEFAULT NULL,
  `issued_by` bigint unsigned NOT NULL,
  `starts_at` timestamp NOT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','completed','appealed','overturned','escalated') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `affects_booking` tinyint(1) NOT NULL DEFAULT '1',
  `affects_visibility` tinyint(1) NOT NULL DEFAULT '0',
  `strike_count` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `worker_suspensions_related_shift_id_foreign` (`related_shift_id`),
  KEY `worker_suspensions_issued_by_foreign` (`issued_by`),
  KEY `worker_suspensions_user_id_status_index` (`user_id`,`status`),
  KEY `worker_suspensions_status_ends_at_index` (`status`,`ends_at`),
  KEY `worker_suspensions_reason_category_index` (`reason_category`),
  CONSTRAINT `worker_suspensions_issued_by_foreign` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `worker_suspensions_related_shift_id_foreign` FOREIGN KEY (`related_shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `worker_suspensions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_suspensions`
--

LOCK TABLES `worker_suspensions` WRITE;
/*!40000 ALTER TABLE `worker_suspensions` DISABLE KEYS */;
/*!40000 ALTER TABLE `worker_suspensions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worker_team_members`
--

DROP TABLE IF EXISTS `worker_team_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `worker_team_members` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `team_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `role` enum('leader','member') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'member',
  `status` enum('pending','active','declined','removed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `invited_by` bigint unsigned DEFAULT NULL,
  `invited_at` timestamp NULL DEFAULT NULL,
  `joined_at` timestamp NULL DEFAULT NULL,
  `left_at` timestamp NULL DEFAULT NULL,
  `shifts_with_team` int NOT NULL DEFAULT '0',
  `earnings_with_team` int NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `worker_team_members_team_id_user_id_unique` (`team_id`,`user_id`),
  KEY `worker_team_members_team_id_status_index` (`team_id`,`status`),
  KEY `worker_team_members_user_id_status_index` (`user_id`,`status`),
  KEY `worker_team_members_role_index` (`role`),
  KEY `worker_team_members_invited_by_index` (`invited_by`),
  CONSTRAINT `worker_team_members_invited_by_foreign` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `worker_team_members_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `worker_teams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `worker_team_members_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_team_members`
--

LOCK TABLES `worker_team_members` WRITE;
/*!40000 ALTER TABLE `worker_team_members` DISABLE KEYS */;
/*!40000 ALTER TABLE `worker_team_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worker_teams`
--

DROP TABLE IF EXISTS `worker_teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `worker_teams` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` bigint unsigned NOT NULL,
  `business_id` bigint unsigned DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `avatar_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `max_members` int NOT NULL DEFAULT '10',
  `member_count` int NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `requires_approval` tinyint(1) NOT NULL DEFAULT '1',
  `total_shifts_completed` int NOT NULL DEFAULT '0',
  `average_rating` decimal(3,2) DEFAULT NULL,
  `total_earnings` int NOT NULL DEFAULT '0',
  `specializations` json DEFAULT NULL,
  `preferred_industries` json DEFAULT NULL,
  `min_reliability_score` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `worker_teams_created_by_index` (`created_by`),
  KEY `worker_teams_business_id_index` (`business_id`),
  KEY `worker_teams_is_active_index` (`is_active`),
  KEY `worker_teams_is_public_index` (`is_public`),
  KEY `worker_teams_active_public_idx` (`is_active`,`is_public`),
  CONSTRAINT `worker_teams_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `worker_teams_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_teams`
--

LOCK TABLES `worker_teams` WRITE;
/*!40000 ALTER TABLE `worker_teams` DISABLE KEYS */;
/*!40000 ALTER TABLE `worker_teams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worker_tier_history`
--

DROP TABLE IF EXISTS `worker_tier_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `worker_tier_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `from_tier_id` bigint unsigned DEFAULT NULL,
  `to_tier_id` bigint unsigned NOT NULL,
  `change_type` enum('upgrade','downgrade','initial') COLLATE utf8mb4_unicode_ci NOT NULL,
  `metrics_at_change` json NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `worker_tier_history_from_tier_id_foreign` (`from_tier_id`),
  KEY `worker_tier_history_to_tier_id_foreign` (`to_tier_id`),
  KEY `worker_tier_history_user_id_index` (`user_id`),
  KEY `worker_tier_history_change_type_index` (`change_type`),
  KEY `worker_tier_history_created_at_index` (`created_at`),
  CONSTRAINT `worker_tier_history_from_tier_id_foreign` FOREIGN KEY (`from_tier_id`) REFERENCES `worker_tiers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `worker_tier_history_to_tier_id_foreign` FOREIGN KEY (`to_tier_id`) REFERENCES `worker_tiers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `worker_tier_history_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_tier_history`
--

LOCK TABLES `worker_tier_history` WRITE;
/*!40000 ALTER TABLE `worker_tier_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `worker_tier_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `worker_tiers`
--

DROP TABLE IF EXISTS `worker_tiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `worker_tiers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` int NOT NULL,
  `min_shifts_completed` int NOT NULL DEFAULT '0',
  `min_rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `min_hours_worked` int NOT NULL DEFAULT '0',
  `min_months_active` int NOT NULL DEFAULT '0',
  `fee_discount_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `priority_booking_hours` int NOT NULL DEFAULT '0',
  `instant_payout` tinyint(1) NOT NULL DEFAULT '0',
  `premium_shifts_access` tinyint(1) NOT NULL DEFAULT '0',
  `additional_benefits` json DEFAULT NULL,
  `badge_color` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#6B7280',
  `badge_icon` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `worker_tiers_slug_unique` (`slug`),
  KEY `worker_tiers_level_index` (`level`),
  KEY `worker_tiers_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `worker_tiers`
--

LOCK TABLES `worker_tiers` WRITE;
/*!40000 ALTER TABLE `worker_tiers` DISABLE KEYS */;
/*!40000 ALTER TABLE `worker_tiers` ENABLE KEYS */;
UNLOCK TABLES;
SET @@SESSION.SQL_LOG_BIN = @MYSQLDUMP_TEMP_LOG_BIN;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-20  2:35:44
