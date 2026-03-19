function ChangeColor(tableRow, highLight, kleur)
    {
    if (highLight)
    {
      tableRow.style.backgroundColor = '#dcfac9';
    }
    else
    {
      tableRow.style.backgroundColor = 'green';
    	tableRow.style.backgroundColor = kleur;
     }
  }
