/**
 * For any community, if it has a community agreement, this script will display a checkbox
 * to let users read and accept the agreement before joining the community
*/
  $(document).ready(function() { 
      
      /**
       * Perform an asynchronous HTTP (Ajax) request
       * to check if the given community (by communityId) has a community agreement.
      */
      $.ajax({
               type: "POST",
               url: json.global.webroot+'/communityagreement/config/checkagreement',
               data: {communityId: json.community.community_id},
               cache:false,
               success: function(jsonContent){
                        handldCheckIfAgreementEmptyResponse(jsonContent)}
        });
   }); 

   /**
    * Display community agreement checkbox if the community agreement exists
    * 
    * @param {Array} jsonContent    The JSON string to parse.
    */
   function handldCheckIfAgreementEmptyResponse(jsonContent)
   {
      var agreementNotEmpty = $.parseJSON(jsonContent);
          
      if(agreementNotEmpty == true)
      {
          // display "I read and accepted the agrement" and the checkbox
          var agreement_checkbox = "<div id='communityAgreementCheckboxDiv' class='genericWrapperTopRight'>";
              agreement_checkbox += "<form id=communityAgreementCheckboxForm>";
              agreement_checkbox += "I read and accepted the <a class=\'communityAgreementCheckbox\' href='#'>agreement</a>";
              agreement_checkbox += "<input id='communityAgreement_checkbox'  name='communityAgreement_checkbox' type='checkbox' value='agreement'/>" ;
              agreement_checkbox += "</form>";
              agreement_checkbox += "</div>";
          $('div#joinCommunityDiv').after(agreement_checkbox);

          // pop up the community agreement 
          $('a.communityAgreementCheckbox').click(function()
          {
             loadDialog("agreement","/communityagreement/config/agreementdialog?communityId="+json.community.community_id);
             showBigDialog("Community Agreement");
           });

          // check if a user has accepted the community agreement
          $('form#joinCommunityForm').submit(function(){
             if ( $('form#communityAgreementCheckboxForm input[name=communityAgreement_checkbox]').is(':checked') )  {
                  return true;
             } else {
                  alert('Please accept the community agreement!');
                  return false;
                }
          });
      }
   }