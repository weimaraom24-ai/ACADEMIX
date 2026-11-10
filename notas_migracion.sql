-- Añade el grupo escolar a usuarios para filtrar estudiantes por grado.
ALTER TABLE usuarios
    ADD COLUMN grupo VARCHAR(4) NULL AFTER rol;

-- Guarda una nota única por estudiante y materia, con autor y fecha de cambio.
CREATE TABLE IF NOT EXISTS notas_estudiantes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    estudiante_id INT NOT NULL,
    materia VARCHAR(80) NOT NULL,
    nota DECIMAL(3,1) NOT NULL,
    docente_id INT NULL,
    fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY nota_por_estudiante_materia (estudiante_id, materia),
    KEY notas_docente_id (docente_id)
);