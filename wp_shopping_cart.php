<?php
/*
Plugin Name: WP Simple Pagseguro Shopping cart
Version: v1.0
Plugin URI: http://visie.com.br/pagseguro
Author: Elcio Ferreira
Author URI: http://visie.com.br/
Description: Plugin simples de carrinho de compras <a href="https://pagseguro.uol.com.br/?ind=1118721">PagSeguro</a>, para que você transforme seu blog em uma loja. Baseado no plugin <a href="http://www.tipsandtricks-hq.com/wordpress-simple-paypal-shopping-cart-plugin-768">Simple Paypal Shopping Cart</a>.
*/

/*
    This program is free software; you can redistribute it
    under the terms of the GNU General Public License version 2,
    as published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
*/

session_start();

function ps_shopping_cart_show($content)
{
	if (strpos($content, "<!--show-wp-shopping-cart-->") !== FALSE)
    {
    	if (ps_cart_not_empty())
    	{
        	$content = preg_replace('/<p>\s*<!--(.*)-->\s*<\/p>/i', "<!--$1-->", $content);
        	$matchingText = '<!--show-wp-shopping-cart-->';
        	$replacementText = ps_print_wp_shopping_cart();
        	$content = str_replace($matchingText, $replacementText, $content);
    	}
    }
    return $content;
}

if ($_POST['addcart'])
{
    $count = 1;    
    $products = $_SESSION['pssimpleCart'];
    
    if (is_array($products))
    {
        foreach ($products as $key => $item)
        {
            if ($item['name'] == $_POST['product'])
            {
                $count += $item['quantity'];
                $item['quantity']++;
                unset($products[$key]);
                array_push($products, $item);
            }
        }
    }
    else
    {
        $products = array();
    }
        
    if ($count == 1)
    {
        if (!empty($_POST[$_POST['product']]))
            $price = $_POST[$_POST['product']];
        else
            $price = $_POST['price'];
        
        $product = array('name' => stripslashes($_POST['product']), 'price' => $price, 'quantity' => $count, 'cartLink' => $_POST['cartLink'], 'item_number' => $_POST['item_number']);
        array_push($products, $product);
    }
    
    sort($products);
    $_SESSION['pssimpleCart'] = $products;
}
else if ($_POST['cquantity'])
{
    $products = $_SESSION['pssimpleCart'];
    foreach ($products as $key => $item)
    {
        if (($item['name'] == $_POST['product']) && $_POST['quantity'])
        {
            $item['quantity'] = $_POST['quantity'];
            unset($products[$key]);
            array_push($products, $item);
        }
        else if (($item['name'] == $_POST['product']) && !$_POST['quantity'])
            unset($products[$key]);
    }
    sort($products);
    $_SESSION['pssimpleCart'] = $products;
}
else if ($_POST['delcart'])
{
    $products = $_SESSION['pssimpleCart'];
    foreach ($products as $key => $item)
    {
        if ($item['name'] == $_POST['product'])
            unset($products[$key]);
    }
    $_SESSION['pssimpleCart'] = $products;
}

function ps_print_wp_shopping_cart()
{
	if (!ps_cart_not_empty())
	{
		return;
	}
    $email = get_bloginfo('admin_email');
       
    $defaultEmail = get_option('cart_pagseguro_email');
    $pagseguro_symbol = 'R$';

    if (!empty($defaultEmail))
        $email = $defaultEmail;
     
    $decimal = '.';  
	$urls = '';
	  
	$title = get_option('wp_cart_title');
	if (empty($title)) $title = 'Suas compras';
    
    $output .= '<div class="shopping_cart" style=" padding: 5px;">';
    $output .= "<input type='image' src='".get_bloginfo('wpurl')."/wp-content/plugins/wordpress-pagseguro-shopping-cart/images/shopping_cart_icon.gif' value='Carrinho' title='Carrinho' />";
    $output .= "<h2>";
    $output .= $title;  
    $output .= "</h2>";  
        
    $output .= '<br /><span id="pinfo" style="display: none; font-weight: bold; color: red;">Pressione ENTER para atualizar a quantidade.</span>';
	$output .= '<table style="width: 100%;">';    
    
    $count = 1;
    $total_items = 0;
    $total = 0;
    $form = '';
    if ($_SESSION['pssimpleCart'] && is_array($_SESSION['pssimpleCart']))
    {   
        $output .= '
        <tr>
        <th>Descrição</th><th>Qtde</th><th>Preço</th>
        </tr>';
    
    foreach ($_SESSION['pssimpleCart'] as $item)
    {
        $total += $item['price'] * $item['quantity'];
        
        $total_items +=  $item['quantity'];
    }
    
    foreach ($_SESSION['pssimpleCart'] as $item)
    {
        $output .= "                 
        <tr><td style='overflow: hidden;'><a href='".$item['cartLink']."'>".$item['name']."</a></td>
        <td style='text-align: center'><form method=\"post\"  action=\"\" name='pcquantity' style='display: inline'>
        <input type='hidden' name='product' value='".$item['name']."' />
        
        <input type='hidden' name='cquantity' value='1' /><input type='text' name='quantity' value='".$item['quantity']."' size='1' onchange='document.pcquantity.submit();' onkeypress='document.getElementById(\"pinfo\").style.display = \"\";' /></form></td>
        <td style='text-align: center'>".ps_print_payment_currency(($item['price'] * $item['quantity']), $pagseguro_symbol, $decimal)."</td>
        <td><form method=\"post\"  action=\"\">
        <input type='hidden' name='product' value='".$item['name']."' />
        <input type='hidden' name='delcart' value='1' />
        <input type='image' src='".get_bloginfo('wpurl')."/wp-content/plugins/wordpress-pagseguro-shopping-cart/images/Shoppingcart_delete.gif' value='Remove' title='Remover' /></form></td></tr>
        
        ";
        
        $form .= "
            <input type=\"hidden\" name=\"item_descr_$count\" value=\"".$item['name']."\" />
            <input type=\"hidden\" name=\"item_valor_$count\" value='".str_replace('.','',$item['price'])."' />
            <input type=\"hidden\" name=\"item_quant_$count\" value=\"".$item['quantity']."\" />
            <input type='hidden' name='item_id_$count' value='".$count."' />
        ";
        $form .= "<input type=\"hidden\" name=\"item_frete_$count\" value=\"0\" />";
        $count++;
    }
    }
    
       	$count--;
       	
       	if ($count)
       	{
       		$output .= '<tr><td></td><td></td><td></td></tr>';       
       		$output .= "
       		<tr><td colspan='2' style='font-weight: bold; text-align: right;'>Total: </td><td style='text-align: center'>".ps_print_payment_currency(($total), $pagseguro_symbol, $decimal)."</td><td></td></tr>
       		<tr><td colspan='4'>";

       
              	$output .= "<form action=\"https://pagseguro.uol.com.br/security/webpagamentos/webpagto.aspx\" method=\"post\">$form";
    			if ($count)
            		$output .= '<input type="image" src="'.get_bloginfo('wpurl').'/wp-content/plugins/wordpress-pagseguro-shopping-cart/images/pagseguro_checkout.png" name="submit" alt="Pague com Pagseguro - é rápido, simples e seguro!" />';
       
    			$output .= $urls.'
			    <input type="hidden" name="email_cobranca" value="'.$email.'" />
          <input type="hidden" name="tipo" value="CP">
          <input type="hidden" name="moeda" value="BRL">
			    </form>';          
       	}       
       	$output .= "
       
       	</td></tr>
    	</table></div>
    	";
    
    return $output;
}

function ps_print_wp_cart_button($content)
{          
        $addcart = get_option('addToCartButtonName');
    
        if (!$addcart || ($addcart == '') )
            $addcart = 'Adicionar ao Carrinho';
        
        $pattern = '#\[wp_cart:.+:price:#';
        preg_match_all ($pattern, $content, $matches);
        
        foreach ($matches[0] as $match)
        {            
            $pattern = '[wp_cart:';
            $m = str_replace ($pattern, '', $match);
            $pattern = ':price:';
            $m = str_replace ($pattern, '', $m);
            
            $pieces = explode('|',$m);         
            
            if (sizeof($pieces) == 1)
            {      
                $replacement = '<object><form method="post"  action=""  style="display:inline">
                <input type="submit" value="'.$addcart.'" />
                <input type="hidden" name="product" value="'.$pieces['0'].
                '" /><input type="hidden" name="price" value="';
                
                $content = str_replace ($match, $replacement, $content);
            }   
        }
    
        $forms = str_replace(':item_num:',    
        '" /><input type="hidden" name="shipping" value="',    
        $content);  
               
        $forms = str_replace(':end]',    
        '" /><input type="hidden" name="addcart" value="1" /><input type="hidden" name="cartLink" value="'.ps_cart_current_page_url().'" />
        </form></object>',    
        $forms);
    
    if (empty($forms))
        $forms = $content;
       
    return $forms;
}

function ps_cart_not_empty()
{
        $count = 0;
        if (isset($_SESSION['pssimpleCart']) && is_array($_SESSION['pssimpleCart']))
        {
            foreach ($_SESSION['pssimpleCart'] as $item)
                $count++;
            return $count;
        }
        else
            return 0;
}

function ps_print_payment_currency($price, $symbol, $decimal)
{
    return $symbol.number_format($price, 2, $decimal, ',');
}

function ps_cart_current_page_url() {
 $pageURL = 'http';
 if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
 $pageURL .= "://";
 if ($_SERVER["SERVER_PORT"] != "80") {
  $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
 } else {
  $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
 }
 return $pageURL;
}

function ps_show_ps_wp_cart_options_page () {
	
	$wp_simple_pagseguro_shopping_cart_version = 1.2;
	
    $defaultEmail = get_option('cart_pagseguro_email');
    if (empty($defaultEmail)) $defaultEmail = get_bloginfo('admin_email');
    
    $addcart = get_option('addToCartButtonName');
    if (empty($addcart)) $addcart = 'Adicionar ao Carrinho';           

	$title = get_option('wp_cart_title');
	if (empty($title)) $title = 'Suas compras';
      
	?>
 	<h2>Opções do Carrinho Simples Pagseguro v <?php echo $wp_simple_pagseguro_shopping_cart_version; ?></h2>
 	
 	<p>Para informações e atualizações, por favor, visite::<br />
    <a href="http://visie.com.br/pagseguro/">http://visie.com.br/pagseguro/</a></p>
    
     <fieldset class="options">
    <legend>Como usar:</legend>

    <p>1. Para adicionar um botão 'Adicionar ao Carrinho' simplesmente insira o texto <strong>[wp_cart:NOME-DO-PRODUTO:price:VALOR-DO-PRODUTO:end]</strong> ao artigo ou página, próximo ao produto. Substitua NOME-DO-PRODUTO e VALOR-DO-PRODUTO pelo nome e valor reais, assim: [wp_cart:Enxugador de gelo:price:129.50:end].</p>
	<p>2. Para adicionar o carrinho de compras a um artigo ou página de checkout ou à sidebar simplesmente adicione o texto <strong>&lt;!--show-wp-shopping-cart--&gt;</strong> a um post, página ou sidebar. O carrinho só será visível quando o comprador adicionar pelo menos um produto. 
    </fieldset>
    
 	<?php
 
    echo '
 <form method="post" action="options.php">';
 wp_nonce_field('update-options');
 echo '
<table class="form-table">
<tr valign="top">
<th scope="row">E-mail de cobrança Pagseguro</th>
<td><input type="text" name="cart_pagseguro_email" value="'.$defaultEmail.'" /></td>
</tr>
<tr valign="top">
<th scope="row">Título do carrinho de compras</th>
<td><input type="text" name="wp_cart_title" value="'.$title.'"  /></td>
</tr>

<tr valign="top">
<th scope="row">Texto do botão de adicionar ao carrinho</th>
<td><input type="text" name="addToCartButtonName" value="'.$addcart.'" /></td>
</tr>

</table>

<p class="submit">
<input type="submit" name="Submit" value="Salvar Opções &raquo;" />
<input type="hidden" name="action" value="update" />
<input type="hidden" name="page_options" value="cart_payment_currency,cart_currency_symbol,cart_pagseguro_email,addToCartButtonName,wp_cart_title" />
</p>

 </form>
 ';
}

function ps_wp_cart_options()
{
     echo '<div class="wrap"><h2>Opções do Carrinho Pagseguro</h2>';
     ps_show_ps_wp_cart_options_page();
     echo '</div>';
}

// Display The Options Page
function ps_wp_cart_options_page () 
{
     add_options_page('Carrinho Pagseguro', 'Carrinho Pagseguro', 'manage_options', __FILE__, 'ps_wp_cart_options');  
}

function show_wp_pagseguro_shopping_cart_widget()
{
    echo ps_print_wp_shopping_cart();
}

function wp_pagseguro_shopping_cart_widget_control()
{
    ?>
    <p>
    <? _e("Configure as opções do plugin no menu de opções."); ?>
    </p>
    <?php
}

function widget_wp_pagseguro_shopping_cart_init()
{
    $widget_options = array('classname' => 'widget_wp_pagseguro_shopping_cart', 'description' => __( "Mostra o carrinho de compras Pagseguro.") );
    wp_register_sidebar_widget('wp_pagseguro_shopping_cart_widgets', __('Carrinho Pagseguro'), 'show_wp_pagseguro_shopping_cart_widget', $widget_options);
    wp_register_widget_control('wp_pagseguro_shopping_cart_widgets', __('Carrinho Pagseguro'), 'wp_pagseguro_shopping_cart_widget_control' );
}

// Insert the options page to the admin menu
add_action('admin_menu','ps_wp_cart_options_page');

add_action('init', 'widget_wp_pagseguro_shopping_cart_init');

add_filter('the_content', 'ps_print_wp_cart_button');

add_filter('the_content', 'ps_shopping_cart_show');

?>
