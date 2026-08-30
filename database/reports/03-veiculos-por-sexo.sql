-- 03 - Quem possui mais veículos: homens ou mulheres
-- LEFT JOIN para pessoas sem veículos entrarem com 0 na contagem
-- (com INNER JOIN elas sumiriam do resultado)

SELECT p.sexo,
       COUNT(v.id) AS total_veiculos
FROM leonardo.pessoas p
LEFT JOIN leonardo.veiculos v ON v.pessoa_id = p.id
GROUP BY p.sexo
ORDER BY total_veiculos DESC;
