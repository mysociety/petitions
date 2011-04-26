<h1>Petitions FAQ</h1>

<p>
    This collection of Frequently Asked (or anticipated) Questions addresses some common worries or clarifications. For more thorough instructions
    on how to use the petitions system, remember to look in the <a href="?page=help">contents</a>.
</p>

<ul>
  <li>
    <a href="#q0">How do I do X?  I&rsquo;ve read the instructions and I still can&rsquo;t see how.</a>
  </li>
  <li>
      <a href="#q1">A petition creator/ petition signer claims that they have created/signed a petition, but I can&rsquo;t find any record of them in the admin interface.</a>
  </li>
  <li>
    <a href="#q2">A petition creator/petition signer claims that they have not received a confirmation email.</a>
  </li>
  <li>
    <a href="#q3">A petition creator/petition signer says that they cannot click on the link in their confirmation email.</a>
  </li>
  <li>
    <a href="#q4">A petition signer has changed their email address since signing the petition.</a>
  </li>
  <li>
    <a href="#q5">A petition creator has changed their email address since creating the petition.</a>
  </li>
  <li>
    <a href="#q6">I want to edit the text of a petition.</a>
  </li>
  <li>
    <a href="#q7">Two people share the same email address  &mdash;  why can&rsquo;t they both sign the same petition?</a>
  </li>
  <li>
     <a href="#q8">How is the petition deadline worked out?</a>
  </li>
  <li>
      <a href="#q9">Can the same shortname be used for multiple petitions?</a>
  </li>
  <li>
      <a href="#q10">Should I remove petitions from the website if they are unsuitable?</a>
  </li>
  <li>
     <a href="#q11">I&rsquo;m confused &mdash; the admin interface looks different from the screenshots in this documentation</a>
  </li>
</ul>  
  
</ul>

<div class="faq_question">
    <a name="q0"></a>
    <h2>
      How do I do X?  I&rsquo;ve read the instructions and I still can&rsquo;t see how.
    </h2>
    <p>
      If there is anything that you want to do that is not covered in the admin interface or the user manual then please contact
      <?= $mailto_support_email ?>. 
    </p>
</div>

<div class="faq_question">
    <a name="q1"></a>
    <h2>    
      A petition creator/ petition signer claims that they have created/signed a petition, but I can&rsquo;t find any record of them in the admin interface.
    </h2>
    <p>
      It&rsquo;s most likely that the user has misspelt their email address. At present if an email address is misspelt it&rsquo;s not possible to find a record for a user even if you search for the misspelling, or for something else such as their name.  We are working to fix this!
    </p>
    <p>
      In this situation write back to the user and ask them to resubmit their petition/ re-sign the petition, paying special attention to the spelling of their email address.
    </p>
    <p>
      If the user continues to have trouble, please contact <?= $mailto_support_email ?> for help.
    </p>
</div>

<div class="faq_question">
    <a name="q2"></a>
    <h2>
        A petition creator/ petition signer claims that they have not received a confirmation email
    </h2>
    <p>
        If a user says that they have not received a confirmation email then search for their email address 
        in the admin interface; this will help you to diagnose the problem.
    </p>
    <p>
      There are three possibilities:
    </p>
    <dl>
      <dt>
        You can&rsquo;t find the person at all.
      </dt>
      <dd>
          This probably means that they spelt their email address wrong, or that something went wrong when 	
        they tried to sign/create the petition.  You should advise the user to create or sign the petition again.
      </dd>
      <dt>
      You find an unconfirmed signature/petition.  
      </dt>
      <dd>
        This probably means the confirmation email was stopped by the user&rsquo;s spam filter.  
        You can advise them to check their spam folder, or you can offer to confirm the petition/signature manually for them.
      </dd>
      <dt>
      [for petition signers only] You find a confirmed signature.
      </dt>
      <dd>
          Let the user know that they have already signed the petition and their signature has been recorded (if 
          they try to sign it a second time, 	the system automatically sends them an email alerting them to this).
      </dd>
    </dl>
    <p>
      If the user continues to have trouble, please contact <?= $mailto_support_email ?> for assistance.
    </p>
</div>

<div class="faq_question">
    <a name="q3"></a>
    <h2>
      A petition creator/ petition signer says that they cannot click on the link in their confirmation email
    </h2>
    <p>
      The usual reason for this is that their email program has disabled links in emails. Usually there will be a setting in the email 
      program to reactivate all links, or the user can simply copy and paste the link into their web browser (N.B. the link must be 
      pasted into the URL bar, not into the search box on a website like Google).
    </p>
    <p>
      If the user continues to have problems then you can confirm their signature or petition manually for them in the admin interface.
    </p>
</div>

<div class="faq_question">
    <a name="q4"></a>
    <h2>
      A petition signer has changed their email address since signing the petition
    </h2>
    <p>
      Ask the user to re-sign the petition in question with their new email address, and then remove their original signature.
    </p>
</div>

<div class="faq_question">
    <a name="q5"></a>
    <h2>
      A petition creator has changed their email address since creating the petition
    </h2>
    <p>
      Please contact <?= $mailto_support_email ?> if this happens and ask us to change it for you.
    </p>
</div>

<div class="faq_question">
    <a name="q6"></a>
    <h2>
      I want to edit the text of a petition
    </h2>
    <p>
      It is not possible for councils to edit the text of petitions &mdash; this is for reasons of transparency, 
      so that the public know that the council cannot change the wording.
    </p>
    <p>
      If you have noticed errors in a draft petition (i.e., one that has not yet been approved) then it is best to reject the petition, and ask 
      the petition creator to correct the errors and resubmit the petition. You can then approve it.  If you have a petition that has 
      already been rejected once and it still has errors please contact <?= $mailto_support_email ?> &mdash; rejecting the petition for a second 
      time does not give the creator a second chance to amend and resubmit.
    </p>
    <p>
      If a petition creator has contacted you to ask for corrections to be made to a live petition then please contact 
      <?= $mailto_support_email ?> and ask us to make the changes for you. However, once a petition has started to collect 
      signatures then the best policy is not to change the wording of the petition substantially, as this would change what 
      the early signatories have signed up to &mdash; you may wish to point this out to the petition creator if they have asked for a major amendment.
    </p>
</div>

<div class="faq_question">
    <a name="q7"></a>
    <h2>
      Two people share the same email address &mdash; why can&rsquo;t they both sign the same petition?
    </h2>
    <p>
      It is only possible for an email address to sign a petition once, regardless of how many different names are given; 
      this is to cut down on abuse of the system and spam.
    </p>
    <p>
      If two people genuinely share the same email address then there are two possibilities to work around this:
    </p>
    <ul>
      <li>
        Ask the signers to create another email address, using a free web-based service such as Hotmail, Gmail, etc.  
        The second person can use that email address to sign the petition.
      </li>
      <li>
        If the signers refuse to get a second email address then simply add the second signatory as an offline signature 
        in the admin interface. Their name will not be displayed, but their signature will be added to the tally.
      </li>
    </ul>
</div>

<div class="faq_question">
    <a name="q8"></a>
    <h2>
      How is the petition deadline worked out?
    </h2>
    <p>
      The petition creator states how long they wish the petition to run for, and the system automatically works out what 
      the deadline will be, from the point at which the petition is approved (not created).
    </p>
</div>

<div class="faq_question">
    <a name="q9"></a>
    <h2>
      Can the same shortname be used for multiple petitions?
    </h2>
    <p>
      No, all petitions shortnames must be unique &mdash; this is because the shortname forms the URL that the petition 
      is held under in both the admin interface and on the public website, and can be used as the reference code for 
      that petition by the council.
    </p>
</div>

<div class="faq_question">
    <a name="q10"></a>
    <h2>
      Should I remove petitions from the website if they are unsuitable?
    </h2>
    <p>
      No, try not to remove petitions unless you absolutely must &mdash; this helps the council to be more transparent about 
      the petitions process. Petitions should only be removed in extreme circumstances, usually at the request of the 
      petition creator. Removing a petition removes all trace of it from the public website.  
      In most instances, the best procedure for council transparency is to reject the petition and hide parts of the 
      information from public view as necessary.
    </p>
</div>

<div class="faq_question">
    <a name="q11"></a>
    <h2>
      I&rsquo;m confused &mdash; the admin interface looks different from the screenshots in this documentation.
    </h2>
    <p>
      The screenshots were created in early 2011, and since then some minor cosmetic changes have been implemented 
      (although these should not affect your understanding of the documentation).  Your council may also have 
      requested extra features that are not covered by the basic manual. If you are struggling to follow the 
      documentation or would like extra clarification please contact <?= $mailto_support_email ?> &mdash; we&rsquo;re always 
      happy to offer help and advice.
    </p>
</div>

