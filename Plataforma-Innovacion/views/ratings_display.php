<div id="ratingsDisplay">
    <h4>Calificaciones de la Encuesta</h4>
    <table class="table">
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Peso</th>
                <th>Deseable</th>
                <th>Impacta la Estrategia</th>
                <th>Factible</th>
                <th>Viable</th>
                <th>Promedio</th>
                <th>Promedio Ponderado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ratings as $rating): ?>
            <tr>
                <td><?php echo htmlspecialchars($rating['email']); ?></td>
                <td><?php echo ($rating['weight'] * 100) . '%'; ?></td>
                <td><?php echo $rating['deseable'] ? number_format($rating['deseable'], 2) : '-'; ?></td>
                <td><?php echo $rating['impacta_estrategia'] ? number_format($rating['impacta_estrategia'], 2) : '-'; ?></td>
                <td><?php echo $rating['factible'] ? number_format($rating['factible'], 2) : '-'; ?></td>
                <td><?php echo $rating['viable'] ? number_format($rating['viable'], 2) : '-'; ?></td>
                <td><?php 
                    $valores = array_filter([
                        $rating['deseable'],
                        $rating['impacta_estrategia'],
                        $rating['factible'],
                        $rating['viable']
                    ], function($valor) {
                        return $valor !== null;
                    });
                    // Calcular promedio dividiendo por el número de preguntas contestadas
                    echo count($valores) > 0 ? number_format(array_sum($valores) / count($valores), 2) : '-';
                ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p><strong>Calificación Final Ponderada: <?php echo number_format($finalRating, 2); ?></strong></p>
</div>