<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250222155130 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE wishlist (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_9CE12A31A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE wishlist_product (wishlist_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_4C46D2D7FB8E54CD (wishlist_id), INDEX IDX_4C46D2D74584665A (product_id), PRIMARY KEY(wishlist_id, product_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE wishlist ADD CONSTRAINT FK_9CE12A31A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE wishlist_product ADD CONSTRAINT FK_4C46D2D7FB8E54CD FOREIGN KEY (wishlist_id) REFERENCES wishlist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wishlist_product ADD CONSTRAINT FK_4C46D2D74584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wishlist DROP FOREIGN KEY FK_9CE12A31A76ED395');
        $this->addSql('ALTER TABLE wishlist_product DROP FOREIGN KEY FK_4C46D2D7FB8E54CD');
        $this->addSql('ALTER TABLE wishlist_product DROP FOREIGN KEY FK_4C46D2D74584665A');
        $this->addSql('DROP TABLE wishlist');
        $this->addSql('DROP TABLE wishlist_product');
    }
}
