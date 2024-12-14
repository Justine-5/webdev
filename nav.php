<?php
function renderNav($showSearch = true) {
?>
<nav>
  
  <div class="top-nav">
    <div class="logo-menu">
      <button class="menu"></button>
      <h2><a href="home.php" class="logo">Stuby</a></h2>
    </div>
    
    <?php if ($showSearch): ?>
    <form method="get" action="home.php" class="search search-desktop">
      <input type="search" placeholder="Search Decks">
      <button type="submit" name="search">
        <img src="icons/search.svg" alt="Search">
      </button>
    </form>
    <?php endif; ?>

    <button class="profile">JC</button>
  </div>

  <?php if ($showSearch): ?>
  <form method="get" action="home.php" class="search search-mobile">
    <input type="search" placeholder="Search Decks">
    <button type="submit" name="search">
      <img src="icons/search.svg" alt="Search">
    </button>
  </form>
  <?php endif; ?>
</nav>
<?php
}
?>
