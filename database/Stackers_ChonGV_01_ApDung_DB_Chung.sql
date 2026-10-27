-- STACKERS / CHON GIAO VIEN / 2026-10-26
-- Chay trong phpMyAdmin: chon CSDL cua nhom -> SQL.
-- Su dung dung voi class/chon-giao-vien-bootstrap.php trong patch TichHop_Main_20261026.
-- KHONG thay doi bang nguoidung, truonghoc, giaovien, chi tieu hoac phan cong.
-- Luu y: 2->11 va 3->1 la HAI CAP DANG DUNG DE TEST; chi chay khi ban xac nhan
-- thcs@gmail.com quan ly truong 11 va thpt@gmail.com quan ly truong 1.

CREATE TABLE IF NOT EXISTS `qlgv_quanlytruong` (
  `manguoidung` BIGINT(20) NOT NULL,
  `matruong` BIGINT(20) NOT NULL,
  `ngaytao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`manguoidung`),
  KEY `idx_qlgv_school` (`matruong`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Chi them khi user co dung role, truong co dung loai va mapping chua co.
-- Neu da co mapping KHAC thi lenh khong tu sua; can kiem tra lai trong bang ket qua.
INSERT INTO `qlgv_quanlytruong` (`manguoidung`, `matruong`)
SELECT nd.`manguoidung`, th.`matruong`
FROM `nguoidung` AS nd
JOIN `truonghoc` AS th ON th.`matruong` = 11
WHERE nd.`manguoidung` = 2 AND nd.`vaitro` = 'THCS'
  AND th.`loaitruong` = 'THCS'
  AND NOT EXISTS (
    SELECT 1 FROM `qlgv_quanlytruong` AS q
    WHERE q.`manguoidung` = nd.`manguoidung`
  );

INSERT INTO `qlgv_quanlytruong` (`manguoidung`, `matruong`)
SELECT nd.`manguoidung`, th.`matruong`
FROM `nguoidung` AS nd
JOIN `truonghoc` AS th ON th.`matruong` = 1
WHERE nd.`manguoidung` = 3 AND nd.`vaitro` = 'THPT'
  AND th.`loaitruong` = 'THPT'
  AND NOT EXISTS (
    SELECT 1 FROM `qlgv_quanlytruong` AS q
    WHERE q.`manguoidung` = nd.`manguoidung`
  );

-- XAC MINH: phai hien 2 dong dung (2,THCS,11,THCS) va (3,THPT,1,THPT).
SELECT q.`manguoidung`, nd.`email`, nd.`vaitro`,
       q.`matruong`, th.`tentruong`, th.`loaitruong`,
       CASE WHEN nd.`vaitro` = th.`loaitruong` THEN 'OK'
            ELSE 'SAI_VAI_TRO' END AS `kiemtra`
FROM `qlgv_quanlytruong` AS q
LEFT JOIN `nguoidung` AS nd ON nd.`manguoidung` = q.`manguoidung`
LEFT JOIN `truonghoc` AS th ON th.`matruong` = q.`matruong`
WHERE q.`manguoidung` IN (2, 3)
ORDER BY q.`manguoidung`;
