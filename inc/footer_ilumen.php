<hr style='width:1000px;color:lightblue;' />
<div id="footer">
    <div id="copyrightnotice">
        <p><span class="slogan">iLumen. To Measure is to know</span></p>
        <?php
        
        $start = 2012;
        
        if( $start == date('Y') )
        {
            $str = 2012;
        }else
        {
            $str = $start . "-" . date('Y');
        }
        
        ?>
        
        Copyright <?php echo $str; ?> &copy; iLumen <br />
        </div>
        
        <div id="contactfutech">
        <span class="heading">ILUMEN</span> Ambachtstraat 19 - 3980 Tessenderlo<br />
            <span class="heading">E-MAIL</span> info@ilumen.be<br />
            <span class="heading">TEL</span> +32 13 29 75 08        
        </div>
        <div class="clearfloat"><!-- --></div>
    </div>