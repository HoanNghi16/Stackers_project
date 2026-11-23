-- Run once against the tuyensinh10 database before creating THCS/THPT accounts.
-- School accounts are represented by giaovien rows; subject can be left unset.
ALTER TABLE giaovien
    MODIFY mamonhoc BIGINT NULL;
