<div style="color: #000000">
    <h3>Chat History</h3>
    <table>
        <tr>
            <td style="font-weight: 500; padding: 5px;">Name of Organisation:</td>
            <td style="padding: 5px;"><?php echo $conversation->account->org_name;?></td>
        </tr>
        <tr>
            <td style="font-weight: 500; padding: 5px;">Date:</td>
            <td style="padding: 5px;"><?php echo date('d-m-Y');?></td>
        </tr>
        <tr>
            <td style="font-weight: 500; padding: 5px;">Name of Mentor:</td>
            <td style="padding: 5px;"><?php echo $conversation->coach->name;?></td>
        </tr>
        <tr>
            <td style="font-weight: 500; padding: 5px;">Name of Mentee:</td>
            <td style="padding: 5px;"><?php echo $conversation->coachee->coachee_name;?></td>
        </tr>
    </table>
    <br><br>  
    <table>
        <?php 
        foreach($conversation as $data) {
            foreach($data as $conv) {
                if($conv->chat) {
        ?>
        <?php if($conv->conv_done_by == 'coach') { ?> 
        <tr>
            <td style="padding: 8px; font-weight: 500;" valign="top">
                <?php echo $conversation->coach->name; ?>:
            </td>
            <td style="background-color: #c8f2e8; padding: 8px;">
                <?php echo $conv->chat; ?>
            </td>
        </tr> 
        <br>
        <?php } else { ?>
        <tr>
            <td style="padding: 8px; font-weight: 500" valign="top">
                <?php echo $conversation->coachee->coachee_name;?>:
            </td>
            <td style="background-color: #ffd799; padding: 8px;">
                <?php echo $conv->chat; ?>
            </td>
        </tr> 
        <br>
        <?php } } } } ?>
    </table>
    <br><br>
    <img src="https://www.koach.ai/v2/koachai_convo/coachimage/logo.png" alt="KoachAI Logo" style="width: 150px; height: 40px">
</div>