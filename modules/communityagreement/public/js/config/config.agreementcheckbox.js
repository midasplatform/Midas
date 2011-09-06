 /*
  * For any community, if it has a community agreement, 
  * this page will add a checkbox to let potential community members read and accept the agreement.
  */
 
  $(document).ready(function() { 
      
      $.ajax({
               type: "POST",
               url: json.global.webroot+'/communityagreement/config/checkagreement',
               data: {communityId: json.community.community_id},
               cache:false,
               success: function(jsonContent){
                        handldCheckIfAgreementEmptyResponse(jsonContent) }
        });
   }); 
      
   function handldCheckIfAgreementEmptyResponse(jsonContent)
   {
      var agreementNotEmpty=jQuery.parseJSON(jsonContent);
          
      if(agreementNotEmpty == true)
      {
          var agreement_checkbox = "<div id='communityAgreementCheckboxDiv' class='genericWrapperTopRight'>" ;
              agreement_checkbox += "<form id=communityAgreementCheckboxForm>" ;
              agreement_checkbox += "I read and accepted the <a class=\'communityAgreementCheckbox\' href='#'>agreement</a>" ;
              agreement_checkbox += "<input id='communityAgreement_checkbox'  name='communityAgreement_checkbox' type='checkbox' value='agreement'/>" ;
              agreement_checkbox += "</form>" ;
              agreement_checkbox += "</div>" ;
      
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
