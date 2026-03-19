<hr style='width:1000px;color:lightblue;' />
<div id="footer">
    <div id="copyrightnotice">
        <?php
        $result = mysqli_query($conn, "SELECT * FROM kal_instellingen");
        $row = mysqli_fetch_array($result);
        // id->0, bedrijfnaam->1, bedrijf_straat->2, bedrijf_straatnr->3, bedrijf_postcode->4, 
        // bedrijf_gemeente->5, bedrijf_email->6, bedrijf_tel->7, bedrijf_slogan->8
        ?>
        <p><span class="slogan"><?php echo $row["bedrijf_slogan"] ?></span></p>
        Copyright 
            <?php 
            if(date('Y') == $row['bedrijf_startjaar'])
                {
                    echo $row['bedrijf_startjaar'];                
                }else{
                    echo $row['bedrijf_startjaar'] . ' - ' . date('Y');  
                } 
                ?> 
        &copy; <?php echo $row["bedrijf_naam"] ?> <br />
        </div>
    
        <div id="contactfutech">
        <span class="heading"><?php echo strtoupper($row["bedrijf_naam"]) ?></span><?php echo ' ' .$row["bedrijf_straat"]. ' ' . $row["bedrijf_straatnr"]. ' - ' .$row["bedrijf_postcode"]. ' ' . $row["bedrijf_gemeente"] ?><br />
            <span class="heading">E-MAIL</span> <?php echo $row["bedrijf_email"] ?><br />
            <span class="heading">TEL</span> <?php echo $row["bedrijf_tel"] ?>        
        </div>
        <div class="clearfloat"><!-- --></div>
    </div>