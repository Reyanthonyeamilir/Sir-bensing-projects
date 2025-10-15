
// assets/app.js


// Main JavaScript entry point
console.log('Encore JS loaded');

// ✅ app.js
import DataTable from 'datatables.net-dt';
import 'datatables.net-dt/css/dataTables.dataTables.css';

document.addEventListener('DOMContentLoaded', () => {
  const table = document.querySelector('#petTable');
  if (table) {
    new DataTable(table);
  }
})