set names 'utf8';

create table if not exists `schema_version` (
    `date` datetime not null,
    `version` int unsigned not null
);

drop function if exists `get_schema_version`;
delimiter //
create function `get_schema_version`()
    returns int
    reads sql data
    begin
        select ifnull((select `version` from schema_version order by `version` desc, `date` desc limit 1), 0) into @version;
        return @version;
    end//
delimiter ;

drop function if exists `update_schema_version`;
delimiter //
create function `update_schema_version`()
    returns varchar(40)
    reads sql data
    begin
        set @next = get_schema_version() + 1;
        insert into schema_version (`date`, version) values (now(), @next);
        return concat('Migration version ', @next, ' applied.\n');
    end//
delimiter ;

drop procedure if exists `do_migration`;
delimiter //
create procedure `do_migration`(out output text)
begin

    set @result = '';

    /*
     * VERSION 1 - BASE SCHEMA
     */
    if get_schema_version() = 0 then

        create table if not exists `user` (
            `id` int unsigned not null primary key auto_increment,
            `email` varchar(180) not null unique,
            `roles` json not null,
            `password` varchar(255) not null
        );

        create table if not exists `user_options` (
            `id` int unsigned not null primary key auto_increment,
            `user_id` int unsigned not null,
            `compact_view` tinyint(1) not null,
            key `idx_user_id` (`user_id`),
            constraint `fk_user_options_user_id` foreign key `user_options`(`user_id`) references `user`(`id`)
        );

        create table if not exists `pet` (
            `id` int unsigned not null primary key auto_increment,
            `user_id` int unsigned not null,
            `call_name` varchar(255) not null,
            `show_name` varchar(255) not null,
            `pic` varchar(36),
            `notes` longtext not null,
            `type` int unsigned not null,
            `retired` tinyint(1) not null,
            `hash` varchar(8) not null,
            `added_on` datetime not null,
            `modified_on` datetime,
            `sex` tinyint(1),
            `prefix` varchar(255),
            `hexer_or_breeder` varchar(255),
            `birthday` date,
            key `idx_user_id` (`user_id`),
            constraint `fk_pet_user_id` foreign key `pet`(`user_id`) references `user`(`id`)
        );

        create table if not exists `tag` (
            `id` int unsigned not null primary key auto_increment,
            `user_id` int unsigned not null,
            `name` varchar(255) not null,
            `hash` varchar(8) not null,
            key `idx_user_id` (`user_id`),
            constraint `fk_tag_user_id` foreign key `tag`(`user_id`) references `user`(`id`)
        );

        create table if not exists `pet_tags` (
            `tag_id` int unsigned not null primary key auto_increment,
            `pet_id` int unsigned not null,
            key `idx_pet_id` (`pet_id`),
            constraint `fk_pet_tags_pet_id` foreign key `pet_tags`(`pet_id`) references `pet`(`id`)
        );

        create table if not exists `points` (
            `id` int unsigned not null primary key auto_increment,
            `pet_id` int unsigned not null,
            `show_type` int unsigned not null,
            `points` int unsigned not null,
            `added_on` datetime not null,
            `modified_on` datetime,
            key `idx_pet_id` (`pet_id`),
            constraint `fk_points_pet_id` foreign key `points`(`pet_id`) references `pet`(`id`)
        );

        create table if not exists `points_rollup` (
            `id` int unsigned not null primary key auto_increment,
            `pet_id` int unsigned not null,
            `show_type` int unsigned not null,
            `total` int unsigned not null,
            key `idx_pet_id` (`pet_id`),
            constraint `fk_points_rollup_pet_id` foreign key `points_rollup`(`pet_id`) references `pet`(`id`)
        );

        set @result = concat(@result, update_schema_version());

    end if;

    /*
     * VERSION 2 - NEW STUFF
     */
    if get_schema_version() = 1 then

        alter table user add column `verified` tinyint(1) not null,
        add column `verification_token` varchar(255);

        set @result = concat(@result, update_schema_version());
    end if;

    /*
     * VERSION 3 - Add unique constraint so we can INSERT ... ON DUPLICATE KEY UPDATE into rollup table
     */
    if get_schema_version() = 2 then
        alter table points_rollup
            add constraint points_rollup_unique
                unique (pet_id, show_type);

        set @result = concat(@result, update_schema_version());
    end if;

    /*
     * VERSION 4 - Add user creation time
     */
    if get_schema_version() = 3 then
        alter table `user` add column dateAdded datetime default now();
        update `user` set dateAdded = '2023-08-16';
        update `user` set dateAdded = '2023-05-01' where id in (1,2);
        update `user` set roles = '{"0": "ROLE_ADMIN"}' where id = 1;

        set @result = concat(@result, update_schema_version());
    end if;

    /*
     * VERSION 5 - Password reset
     */
    if get_schema_version() = 4 then
        alter table `user` add column resetInitiated datetime default null,
            add column resetToken varchar(32) default null;

        set @result = concat(@result, update_schema_version());
    end if;

    /*
     * VERSION 6 - Multiple pet pics
     */
    if get_schema_version() = 5 then
        create table if not exists `pet_pics` (
            `id` int unsigned not null primary key auto_increment,
            `pet_id` int unsigned not null,
            `file` varchar(60),
            `order` int unsigned not null
        );

        insert into pet_pics (pet_id, `file`, `order`) select id as pet_id, pic, 1 from pet where pic is not null;

        alter table pet drop column pic;

        alter table user add column `hash` varchar(8) not null;

        alter table schema_version add column scriptExecuted bit default null;

        set @result = concat(@result, update_schema_version());
    end if;

    /*
     * VERSION 7 - Profiles, Unicode stuff
     */
    if get_schema_version() = 6 then
        create table if not exists user_profile (
            `id` int unsigned not null primary key auto_increment,
            `user_id` int unsigned not null,
            `description` varchar(1000) character set utf8mb4 collate utf8mb4_0900_ai_ci default null,
            `username` varchar(50) default null,
            `private` bit default 1,
            `pic` varchar(60) default null
        ) character set utf8mb4 collate utf8mb4_0900_ai_ci;

        ALTER DATABASE petzshowcompanion CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

        ALTER TABLE pet CONVERT TO CHARACTER SET utf8mb4 collate utf8mb4_0900_ai_ci;
        ALTER TABLE tag CONVERT TO CHARACTER SET utf8mb4 collate utf8mb4_0900_ai_ci;

        alter table pet modify call_name varchar(255) character set utf8mb4 collate utf8mb4_0900_ai_ci;
        alter table pet modify show_name varchar(255) character set utf8mb4 collate utf8mb4_0900_ai_ci;
        alter table pet modify prefix varchar(255) character set utf8mb4 collate utf8mb4_0900_ai_ci;
        alter table pet modify hexer_or_breeder varchar(255) character set utf8mb4 collate utf8mb4_0900_ai_ci;
        alter table pet modify notes longtext character set utf8mb4 collate utf8mb4_0900_ai_ci;
        alter table tag modify `name` varchar(255) character set utf8mb4 collate utf8mb4_0900_ai_ci;

        set @result = concat(@result, update_schema_version());
    end if;

    /*
     * VERSION 7 - ProfileSettings stuff. Why I put the table creation in the last version I have no idea...
     */
    if get_schema_version() = 7 then
        alter table user_profile add column `display_name` varchar(100);
        alter table user_profile add column `website` varchar(200);
        alter table user_profile add column `pic_height` int;
        alter table user_profile add column `pic_width` int;
        alter table user_profile add column `hide_name` bool default false;

        insert into user_profile (user_id, private)
        select id, 1 from user;

        alter table user add column `lastLogin` datetime default null;

        alter table tag add column private bit not null default 1;

        create table if not exists `privacy` (
            `id` int unsigned not null primary key auto_increment,
            `user_id` int unsigned not null,
            `call_name` bool not null,
            `show_name` bool not null,
            `notes` bool not null,
            `type` bool not null,
            `retired` bool not null,
            `sex` bool not null,
            `prefix` bool not null,
            `hexer_or_breeder` bool not null,
            `birthday` bool not null
        );

        alter table `pet` add column private bool not null default false;

        alter table `user` add column require_verification bool not null default true;
        -- Currently present users will not require email verification, but anyone registering after this release will.
        update `user` set require_verification = false;

        set @result = concat(@result, update_schema_version());
    end if;

    select @result into output;

end//
delimiter ;

call do_migration(@result);
set @result = coalesce(nullif(@result, ' '), concat('Up to date at version ', get_schema_version(), '.'));
select @result;

drop procedure if exists `do_migration`;
drop function if exists `update_schema_version`;
