-- 07 - Pessoas por sexo, com idade média
-- age(nascimento) devolve o intervalo até hoje;
-- EXTRACT(YEAR FROM ...) pega os anos desse intervalo

SELECT p.sexo,
       COUNT(*) AS quantidade,
       ROUND(AVG(EXTRACT(YEAR FROM age(p.data_nascimento)))) AS idade_media
FROM leonardo.pessoas p
GROUP BY p.sexo;
