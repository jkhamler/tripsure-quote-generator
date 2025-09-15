# Customer Table
CREATE TABLE `customer`
(
    `id`            BIGINT       NOT NULL AUTO_INCREMENT,
    `first_name`    VARCHAR(100) NOT NULL,
    `last_name`     VARCHAR(100) NOT NULL,
    `date_of_birth` DATE         NOT NULL,
    `email`         VARCHAR(255) NOT NULL,
    `phone`         VARCHAR(20)       DEFAULT NULL,
    `created_at`    TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

# Vehicle Table
CREATE TABLE `vehicle`
(
    `id`          BIGINT        NOT NULL AUTO_INCREMENT,
    `customer_id` BIGINT        NOT NULL,
    `model_name`  VARCHAR(100)  NOT NULL,
    `value`       DECIMAL(9, 2) NOT NULL,
    `year`        SMALLINT      NOT NULL,
    `created_at`  TIMESTAMP     NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP     NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_vehicle_customer` FOREIGN KEY (`customer_id`)
        REFERENCES `customer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    KEY `idx_vehicle_customer` (`customer_id`),
    KEY `idx_vehicle_year` (`year`),
    KEY `idx_vehicle_value` (`value`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

# Quote Table
CREATE TABLE `quote`
(
    `id`           BIGINT         NOT NULL AUTO_INCREMENT,
    `customer_id`  BIGINT         NOT NULL,
    `vehicle_id`   BIGINT         NOT NULL,
    `quote_amount` DECIMAL(12, 2) NOT NULL,
    `valid_from`   DATE           NOT NULL,
    `valid_until`  DATE           NOT NULL,
    `created_at`   TIMESTAMP      NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_quote_customer` (`customer_id`),
    KEY `idx_quote_vehicle` (`vehicle_id`),
    KEY `idx_quote_validity` (`valid_from`, `valid_until`),
    KEY `idx_quote_created` (`created_at`),
    KEY `idx_quote_customer_vehicle` (`customer_id`, `vehicle_id`),
    CONSTRAINT `fk_quote_customer` FOREIGN KEY (`customer_id`)
        REFERENCES `customer` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_quote_vehicle` FOREIGN KEY (`vehicle_id`)
        REFERENCES `vehicle` (`id`) ON DELETE CASCADE,
    CONSTRAINT `quote_chk_amount` CHECK (`quote_amount` >= 0)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;