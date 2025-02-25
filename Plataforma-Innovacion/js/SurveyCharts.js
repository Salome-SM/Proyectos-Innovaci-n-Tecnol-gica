// SurveyCharts.js
import React, { useState, useEffect } from 'react';
import { BarChart, Bar, XAxis, YAxis, Tooltip, Legend, PieChart, Pie, Cell, ResponsiveContainer } from 'recharts';

const COLORS = ['#002060', '#FFC000', '#FF0000'];

const SurveyCharts = () => {
  const [data, setData] = useState([]);

  useEffect(() => {
    fetch('api_surveys.php')
      .then(response => response.json())
      .then(data => setData(data))
      .catch(error => console.error('Error:', error));
  }, []);

  const prepareDataByPerson = () => {
    return data;
  };

  const prepareDataByArea = () => {
    const areaData = data.reduce((acc, item) => {
      if (!acc[item.area]) {
        acc[item.area] = { Ideas: 0, Retos: 0, Problemas: 0 };
      }
      acc[item.area].Ideas += item.ideas;
      acc[item.area].Retos += item.retos;
      acc[item.area].Problemas += item.problemas;
      return acc;
    }, {});
    return Object.entries(areaData).map(([name, values]) => ({ name, ...values }));
  };

  const prepareDataByType = () => {
    const totalIdeas = data.reduce((sum, item) => sum + item.ideas, 0);
    const totalRetos = data.reduce((sum, item) => sum + item.retos, 0);
    const totalProblemas = data.reduce((sum, item) => sum + item.problemas, 0);
    const total = totalIdeas + totalRetos + totalProblemas;
    return [
      { name: 'Ideas', value: (totalIdeas / total) * 100 },
      { name: 'Retos', value: (totalRetos / total) * 100 },
      { name: 'Problemas', value: (totalProblemas / total) * 100 }
    ];
  };

  return (
    <div style={{ fontFamily: 'Arial, sans-serif' }}>
      <h2 style={{ color: '#002060', textAlign: 'center' }}>Indicadores de Encuestas</h2>
      
      <h3 style={{ color: '#002060' }}>Número de Ideas/Retos/Problemas por Persona</h3>
      <ResponsiveContainer width="100%" height={300}>
        <BarChart data={prepareDataByPerson()} margin={{ top: 20, right: 30, left: 20, bottom: 5 }}>
          <XAxis dataKey="name" />
          <YAxis />
          <Tooltip />
          <Legend />
          <Bar dataKey="ideas" name="Ideas" fill="#002060" />
          <Bar dataKey="retos" name="Retos" fill="#FFC000" />
          <Bar dataKey="problemas" name="Problemas" fill="#FF0000" />
        </BarChart>
      </ResponsiveContainer>

      <h3 style={{ color: '#002060' }}>Número de Ideas/Retos/Problemas por Área</h3>
      <ResponsiveContainer width="100%" height={300}>
        <BarChart data={prepareDataByArea()} margin={{ top: 20, right: 30, left: 20, bottom: 5 }}>
          <XAxis dataKey="name" />
          <YAxis />
          <Tooltip />
          <Legend />
          <Bar dataKey="Ideas" fill="#002060" />
          <Bar dataKey="Retos" fill="#FFC000" />
          <Bar dataKey="Problemas" fill="#FF0000" />
        </BarChart>
      </ResponsiveContainer>

      <h3 style={{ color: '#002060' }}>Distribución de Ideas/Retos/Problemas (%)</h3>
      <ResponsiveContainer width="100%" height={300}>
        <PieChart>
          <Pie
            data={prepareDataByType()}
            cx="50%"
            cy="50%"
            labelLine={false}
            outerRadius={80}
            fill="#8884d8"
            dataKey="value"
            label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
          >
            {prepareDataByType().map((entry, index) => (
              <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
            ))}
          </Pie>
          <Tooltip />
        </PieChart>
      </ResponsiveContainer>
    </div>
  );
};

export default SurveyCharts;