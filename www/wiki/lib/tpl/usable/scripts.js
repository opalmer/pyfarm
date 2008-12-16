function toggleNavSearchFocus()
{
  theElement = document.getElementById('navSearch');
  if (theElement.value == 'search')
  {
    theElement.value = "";
    theElement.style.color = "#000000";
  }
}

function toggleNavSearchBlur()
{
  theElement = document.getElementById('navSearch');
  
  if (theElement.value == '')
  {
    theElement.style.color = "#808080"
    theElement.value = "search";
  }
}