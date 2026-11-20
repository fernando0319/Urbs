-- Urbs — esquema MySQL 5.7+ / MariaDB 10.3+
-- Compatível com hospedagem compartilhada (sem tipos espaciais obrigatórios).

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NULL UNIQUE,
  password_hash VARCHAR(255) NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'user',
  profile_type VARCHAR(30) NOT NULL DEFAULT 'citizen',
  bio TEXT NULL,
  avatar VARCHAR(190) NULL,
  registry VARCHAR(60) NULL,
  professional_verified_at DATETIME NULL,
  resident_verified_at DATETIME NULL,
  email_verified_at DATETIME NULL,
  blocked_at DATETIME NULL,
  block_reason VARCHAR(255) NULL,
  consent_at DATETIME NULL,
  deleted_at DATETIME NULL,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  color VARCHAR(9) NOT NULL DEFAULT '#2563eb',
  sort INT NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS issues (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  kind VARCHAR(20) NOT NULL DEFAULT 'problem',
  category_id INT UNSIGNED NULL,
  title VARCHAR(160) NOT NULL,
  description TEXT NOT NULL,
  lat DECIMAL(10,7) NOT NULL,
  lng DECIMAL(10,7) NOT NULL,
  address VARCHAR(255) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'open',
  moderation VARCHAR(20) NOT NULL DEFAULT 'pending',
  support_count INT NOT NULL DEFAULT 0,
  support_verified_count INT NOT NULL DEFAULT 0,
  proposal_count INT NOT NULL DEFAULT 0,
  comment_count INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_issues_geo (lat, lng),
  INDEX idx_issues_mod (moderation, created_at),
  INDEX idx_issues_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS issue_photos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  issue_id INT UNSIGNED NOT NULL,
  file VARCHAR(190) NOT NULL,
  thumb VARCHAR(190) NOT NULL,
  sort INT NOT NULL DEFAULT 0,
  INDEX idx_photos_issue (issue_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proposals (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  issue_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(160) NOT NULL,
  summary VARCHAR(300) NOT NULL,
  description TEXT NOT NULL,
  estimated_cost DECIMAL(14,2) NULL,
  cost_notes VARCHAR(255) NULL,
  license VARCHAR(20) NOT NULL DEFAULT 'cc-by',
  before_file VARCHAR(190) NULL,
  after_file VARCHAR(190) NULL,
  cover_thumb VARCHAR(190) NULL,
  moderation VARCHAR(20) NOT NULL DEFAULT 'pending',
  preference_count INT NOT NULL DEFAULT 0,
  preference_verified_count INT NOT NULL DEFAULT 0,
  comment_count INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_proposals_issue (issue_id),
  INDEX idx_proposals_user (user_id),
  INDEX idx_proposals_mod (moderation, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proposal_files (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  proposal_id INT UNSIGNED NOT NULL,
  kind VARCHAR(10) NOT NULL,
  file VARCHAR(190) NOT NULL,
  thumb VARCHAR(190) NULL,
  original_name VARCHAR(190) NULL,
  size INT NOT NULL DEFAULT 0,
  sort INT NOT NULL DEFAULT 0,
  INDEX idx_pfiles_proposal (proposal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supports (
  user_id INT UNSIGNED NOT NULL,
  issue_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (user_id, issue_id),
  INDEX idx_supports_issue (issue_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS preferences (
  user_id INT UNSIGNED NOT NULL,
  issue_id INT UNSIGNED NOT NULL,
  proposal_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (user_id, issue_id),
  INDEX idx_pref_proposal (proposal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  target_type VARCHAR(20) NOT NULL,
  target_id INT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  moderation VARCHAR(20) NOT NULL DEFAULT 'approved',
  created_at DATETIME NOT NULL,
  INDEX idx_comments_target (target_type, target_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS status_updates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  issue_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  status VARCHAR(20) NOT NULL,
  note VARCHAR(500) NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_status_issue (issue_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reports (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  target_type VARCHAR(20) NOT NULL,
  target_id INT UNSIGNED NOT NULL,
  reason VARCHAR(40) NOT NULL,
  details VARCHAR(1000) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'open',
  resolved_by INT UNSIGNED NULL,
  resolved_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_reports_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS verification_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  type VARCHAR(20) NOT NULL,
  registry VARCHAR(60) NULL,
  notes VARCHAR(1000) NULL,
  file VARCHAR(190) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  reviewed_by INT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_verif_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tokens (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  type VARCHAR(30) NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  INDEX idx_tokens_hash (token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  action VARCHAR(40) NOT NULL,
  akey CHAR(64) NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_attempts (action, akey, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mod_log (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  moderator_id INT UNSIGNED NOT NULL,
  action VARCHAR(40) NOT NULL,
  target_type VARCHAR(20) NOT NULL,
  target_id INT UNSIGNED NOT NULL,
  note VARCHAR(500) NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_modlog_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  k VARCHAR(60) PRIMARY KEY,
  v TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- v2: projetos comunitários e sugestões

CREATE TABLE IF NOT EXISTS projects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  proposal_id INT UNSIGNED NOT NULL,
  issue_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(160) NOT NULL,
  plan TEXT NOT NULL,
  duty_type VARCHAR(20) NOT NULL,
  organization VARCHAR(160) NOT NULL,
  organization_doc VARCHAR(30) NULL,
  organization_verified_at DATETIME NULL,
  goal_amount DECIMAL(14,2) NULL,
  raised_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  raised_updated_at DATETIME NULL,
  campaign_url VARCHAR(500) NULL,
  payment_info TEXT NULL,
  auth_status VARCHAR(20) NOT NULL DEFAULT 'not_requested',
  auth_body VARCHAR(160) NULL,
  auth_protocol VARCHAR(80) NULL,
  auth_requested_at DATE NULL,
  auth_decided_at DATE NULL,
  auth_notes TEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'preparing',
  moderation VARCHAR(20) NOT NULL DEFAULT 'pending',
  comment_count INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_projects_proposal (proposal_id),
  INDEX idx_projects_issue (issue_id),
  INDEX idx_projects_mod (moderation, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_documents (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  kind VARCHAR(20) NOT NULL,
  title VARCHAR(160) NOT NULL,
  amount DECIMAL(14,2) NULL,
  file VARCHAR(190) NOT NULL,
  original_name VARCHAR(190) NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_pdocs_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_expenses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  description VARCHAR(255) NOT NULL,
  supplier VARCHAR(160) NULL,
  amount DECIMAL(14,2) NOT NULL,
  spent_on DATE NOT NULL,
  file VARCHAR(190) NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_pexp_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_updates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  kind VARCHAR(20) NOT NULL DEFAULT 'post',
  body TEXT NOT NULL,
  photo VARCHAR(190) NULL,
  photo_thumb VARCHAR(190) NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_pupd_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS project_pledges (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  kind VARCHAR(20) NOT NULL,
  description VARCHAR(500) NOT NULL,
  amount DECIMAL(14,2) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'offered',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_pledges_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proposal_suggestions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  proposal_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  image VARCHAR(190) NULL,
  image_thumb VARCHAR(190) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'open',
  author_reply VARCHAR(1000) NULL,
  replied_at DATETIME NULL,
  support_count INT NOT NULL DEFAULT 0,
  moderation VARCHAR(20) NOT NULL DEFAULT 'approved',
  created_at DATETIME NOT NULL,
  INDEX idx_sugg_proposal (proposal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS suggestion_supports (
  user_id INT UNSIGNED NOT NULL,
  suggestion_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (user_id, suggestion_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proposal_revisions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  proposal_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  note VARCHAR(1000) NOT NULL,
  prev_after_file VARCHAR(190) NULL,
  incorporated VARCHAR(500) NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_rev_proposal (proposal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
