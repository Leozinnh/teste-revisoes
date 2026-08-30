-- 05 - Marcas distintas por sexo do proprietário
-- COUNT(DISTINCT marca): cada marca conta uma vez só,
-- mesmo que a pessoa tenha vários veículos da mesma marca

SELECT p.sexo,
       COUNT(DISTINCT v.marca) AS total_marcas
FROM leonardo.veiculos v
JOIN leonardo.pessoas p ON p.id = v.pessoa_id
GROUP BY p.sexo
ORDER BY total_marcas DESC;
