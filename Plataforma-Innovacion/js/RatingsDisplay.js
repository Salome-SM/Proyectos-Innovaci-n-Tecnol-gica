import React, { useState, useEffect } from 'react';

const RatingsDisplay = () => {
  const [ratingsData, setRatingsData] = useState([]);

  useEffect(() => {
    fetch('api_ratings.php')
      .then(response => response.json())
      .then(data => setRatingsData(data))
      .catch(error => console.error('Error:', error));
  }, []);

  return (
    <div>
      <h2>Calificaciones de Encuestas</h2>
      <table>
        <thead>
          <tr>
            <th>ID Encuesta</th>
            <th>Calificadores</th>
            <th>Promedio Ponderado</th>
          </tr>
        </thead>
        <tbody>
          {ratingsData.map((survey) => (
            <tr key={survey.surveyId}>
              <td>{survey.surveyId}</td>
              <td>
                {survey.ratings.map(rating => (
                  <div key={rating.email}>
                    {rating.email}: {rating.rating.toFixed(2)} (Peso: {(rating.weight * 100).toFixed(0)}%)
                  </div>
                ))}
              </td>
              <td>{survey.finalRating.toFixed(2)}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};

export default RatingsDisplay;