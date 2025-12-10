
// assets/app.js


// Main JavaScript entry point
console.log('Encore JS loaded');

import DataTable from 'datatables.net-dt';
import 'datatables.net-dt/css/dataTables.dataTables.css';

document.addEventListener('DOMContentLoaded', () => {
  // ✅ Initialize DataTable
  const table = document.querySelector('#petTable',
                '#userTable');
  if (table) {
    new DataTable(table, {
      responsive: true,
      pageLength: 10,
      order: [[0, 'desc']], // sort by ID
      dom: '<"top-bar"lf>rt<"bottom"ip>', 
      // l = length dropdown (entries)
      // f = search filter
      // t = table
      // i = info text
      // p = pagination
    });
  }

  // ✅ Toggle description expand/collapse
  document.querySelectorAll('.toggle-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const text = btn.previousElementSibling;
      text.classList.toggle('expanded');
      btn.textContent = text.classList.contains('expanded') ? 'Read less' : 'Read more';
    });
  });

  
});

import './styles/app.css';
  
import './styles/crud.css';
// import './styles/adminstaffsdbar.css';
// import './styles/dashboard.css';
// import './styles/index.css';
// import './styles/orders.css';
// import './styles/petproducts.css';
// import './styles/product_view.css';
// import './styles/product.css';



