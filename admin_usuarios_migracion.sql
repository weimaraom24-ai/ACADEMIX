-- Añade estado de cuenta para poder desactivar usuarios sin borrar su historial.
ALTER TABLE usuarios
    ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER rol;
