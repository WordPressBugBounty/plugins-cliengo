var j = jQuery.noConflict();
var app = new Vue({
  el: '#app',
  data: {
    id: null,
    regData: {email: userEmail, name: '', password: '', website: siteUrl, errors: {}},
    chatbot_token: '',
    position_chatbot:'right',
    option_select: '',
    alertDanger:false,
    alertSuccess:false,
    nameRegex: /^[a-zA-Z][^0-9_!¡?÷?¿\\+=@#$%ˆ\/&*(){}|~<>;:,.-[\]]{2,}$/,
    emailRegex: /[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/,
    websiteRegex: /^(https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|www\.[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[^\s]{2,}|https?:\/\/(?:www\.|(?!www))[a-zA-Z0-9]+\.[^\s]{2,}|www\.[a-zA-Z0-9]+\.[^\s]{2,}|[a-zA-Z0-9]+\.[^\s]{2,})$/,
    login: {
      loggedIn: false,
      showConfigForm: false,
      disableWebsiteSelector: false,
      tokenInputSelected: false,
      email: null,
      password: null
    },
    loading: {login: false, reg: false, chatPos: false, selectWeb: false, rendering: true},
    messages: {
      token: {tokenValidError: false, tokenSuccess: false, tokenUpdateError: false},
      reg: {shortPassword: false, invalidWebsite: false, missingInfo: false, serverError: false},
      login: {success: false, invalidCredentials: false, otherError: false, loggedOut: false},
      position: {success: false},
      loggedOut: {success: false}
    },
    company: {
      companyId: null,
      websites: [],  // Array items: { id: string, title: string, url: string }
    },
    selectedWebsiteId: null,
    showModal: false,
    highlight: false, // meant to highlight chatbot selection section
    showPasswords: {
      login: false,
      registration: false
    },
    errors: {
      loginEmail: false,
      loginPassword: false,
      registrationName: false,
      registrationEmail: false,
      registrationPassword: false,
      registrationWebsite: false
    }
  },
  beforeMount: function () {
    this.restoreSession();
  },
  updated: function() {
    if (app.highlight) {
      j('#selectedWebsite').focus();
    }
  },
  methods: {
    /**
     * Validates the chatbot token input
     * @param chatbot_token
     * @returns {boolean}
     */
    validateChatbotTokenField:function(chatbot_token) {
      if (!chatbot_token) {
        return false;
      }
      var regular_expression_company_id  = /[0-9A-Fa-f]{24}/g; //validate field hexadecimal 24 caracteres
      var regular_expression_website_id  = /[0-9A-Fa-f]{24}/g; //validate field hexadecimal 24 caracteres
      var array_chatbot_token = chatbot_token.split("-");

      var company_id = array_chatbot_token[0];
      var website_id = array_chatbot_token[1];

      if (company_id && website_id) {
        return regular_expression_company_id.test(company_id) && regular_expression_website_id.test(website_id);
      } else {
        return false;
      }
    },
    /**
     * Checks whether, given the restored account info, session exists or not
     * @param account
     */
    checkSessionExists: function(account) {
      if (account) {
        app.saveResponse(account);
        // Show saved website selection
        if (account.selectedWebsiteId) {
          app.selectedWebsiteId = account.selectedWebsiteId;
        } else {
          // Highlight Chatbot Selection section to catch user attention
          app.beginHighlightAnimation();
        }
        app.showConfigForm(true);
        return true;
      }
      return false;
    },
    /**
     * Checks whether, given the restored token info, session exists or not
     * @param token - persisted chatbot token
     */
    checkExistsChatbotToken:function(token) {
      if (token) {
        app.chatbot_token = token;
        // Update and show config form
        app.updateToken(true);
      }
    },
    /**
     * Restores session variables like chatbot token, account information and chatbot position
     * and renders view accordingly.
     */
    restoreSession: function() {
      j.ajax({
        type:"GET",
        url: ajaxurl,
        data: {
          action: 'restore_session'
        },
        success:function(response) {
          response = JSON.parse(response);

          // Restore saved chatbot position
          if (response.position === 'left') {
            app.position_chatbot = 'left';
          }

          // Restore saved account information
          if (!app.checkSessionExists(response.account)) {
            // check if user 'logged' through chatbot token input.
            app.checkExistsChatbotToken(response.token);
          }

          app.loading.rendering = false;

          // If both account and token are empty, pre-select form
          app.option_select = 'true';
        },
        error: function(error){
          app.loading.rendering = false;
          console.log(error);
        }
      });
    },
    /**
     * Generates and sets a chatbot token for the current company and selected website, only if the data is present.
     * Checks `app.company.companyId` and `app.selectedWebsiteId`.
     */
    generateChatbotToken: function() {
        if (!(app.company.companyId && app.selectedWebsiteId)) {
            return;
        }
        app.chatbot_token = `${app.company.companyId}-${app.selectedWebsiteId}`;
    },
    updateChatbotToken:function() {
      app.clearMessages();
      if (this.validateChatbotTokenField(app.chatbot_token)) {
        app.login.loading = true;
        j.ajax({
          type:"POST",
          url: ajaxurl,
          data: {
            action: 'update_chatbot_token',
            chatbot_token: app.chatbot_token,
            position_chatbot: app.position_chatbot,
            update_chatbot_token_nonce: jQuery('#update_chatbot_token_nonce').val()
          },
          success:function(response) {
            if (response) {
              app.alertSuccess = true;
              // show installation successful modal
              app.showModal = true;
            }else{
              app.messages.token.tokenUpdateError = true;
              app.alertDanger  = true;
            }
            app.loading.login = false;
          },
          error: function(error) {
            console.log(error);
            app.login.reg = false;
            app.loading.login = false;
          }
        });
      } else {
        if (!app.selectedWebsiteId) {
          // Highlight Chatbot Selection section to catch user attention
          app.beginHighlightAnimation();
        }
        app.alertDanger  = true;
        app.messages.tokenValidError = true;
      }
    },
    /**
     * Clears chatbot token in DB
     */
    clearChatbotToken: function() {
      j.ajax({
        type:"POST",
        url: ajaxurl,
        data: {
          action: 'update_chatbot_token',
          chatbot_token: '',
          position_chatbot: app.position_chatbot
        }
      });
    },
    /**
     * Persists the chatbot position preference
     */
    saveChatbotPosition: function() {
      app.clearMessages();
      app.loading.chatPos = true;
      j.ajax({
        type:"POST",
        url: ajaxurl,
        data: {
          action: 'update_chatbot_position',
          position_chatbot: app.position_chatbot,
          update_chatbot_position_nonce: jQuery('#update_chatbot_position_nonce').val()
        },
        success:function(response) {
          if (response) {
            app.messages.position.success = true;
            app.loading.chatPos = false;
          } else {
            app.loading.chatPos = false;
          }
        },
        error: function(error) {
          console.log(error);
        }
      });
    },
    wordPressLogin: function() {
      app.errors.loginEmail = false;
      app.errors.loginPassword = false;
      let hasErrors = false;

      if(!app.emailRegex.test(app.login.email)) {
        app.errors.loginEmail = true;
        hasErrors = true;
      }
      if(!app.login.password || app.login.password.length < 6) {
        app.errors.loginPassword = true;
        hasErrors = true;
      }
      if(hasErrors) {
        return;
      }
      if (app.login.email && app.login.password) {
        app.clearMessages();
        app.loading.login = true;
        var self = this;

        j.ajax({
          type: "POST",
          url: ajaxurl,
          data: {
            action: 'wordpress_login',
            username: app.login.email,
            password: app.login.password,
            wordpress_login_nonce: jQuery('#wordpress_login_nonce').val()
          },
          success: function (response) {
            self.saveResponse(JSON.parse(response));
            self.showConfigForm();
          },
          error: function (error) {
            console.error(error);
            if (error.status === 403) {
              app.messages.login.invalidCredentials = true;
            } else {
              app.messages.login.otherError = true;
            }
            app.alertDanger = true;
            app.loading.login = false;
          }
        });
      }
    },
    /**
     * Performs registration and installs cliengo chatbot
     */
    registerAndSaveToken:function() {
      this.clearMessages();
      let hasErrors = false;

      if(!app.nameRegex.test(app.regData.name.toLowerCase())) {
        app.errors.registrationName = true;
        hasErrors = true;
      }
      if(!app.emailRegex.test(app.regData.email)) {
        app.errors.registrationEmail = true;
        hasErrors = true;
      }
      if(!app.regData.password || app.regData.password.length < 8) {
        app.errors.registrationPassword = true;
        hasErrors = true;
      }
      if(!app.websiteRegex.test(app.regData.website)) {
        app.errors.registrationWebsite = true;
        hasErrors = true;
      }

      if(hasErrors) {
        return;
      }
      var errorMsg = checkForErrors(app.regData);
      if (errorMsg) {
        app.alertDanger = true;
        app.messages.reg[errorMsg] = errorMsg;
      } else {
        app.loading.reg = true;
        var self = this;
        j.ajax({
          type:"POST",
          url: ajaxurl,
          data: {
            action:'wp_registration',
            username: app.regData.name,
            email: app.regData.email,
            password: app.regData.password,
            sourceName: sanitizeUrl(app.regData.website),
            accountName: app.regData.website,
            originUrl: siteUrl,
            wp_registration_nonce: jQuery('#wp_registration_nonce').val()
          },
          success:function(response) {
            // Save response and update chatbot token, then show config form
            self.saveResponse(JSON.parse(response));
            self.showConfigForm();
          },
          error: function(error) {
            app.loading.reg = false;
            app.messages.reg.serverError = JSON.parse(error.responseText).detail;
            app.alertDanger = true;
          }
        });
      }
    },
    /**
     * Saves the server signup/login response into our app
     * @param data
     */
    saveResponse: function(data) {
      const response = typeof data === 'object' ? data : JSON.parse(data);
      app.company = response;
      if (response.websites && response.websites.length === 1) {
        app.selectedWebsiteId = response.websites[0].id;
        app.login.disableWebsiteSelector = true;
      }
      // Persist session
      app.updateSession(response);
    },
    /**
     * Hides any possible loaders and shows the config form
     */
    showConfigForm: function(restoring) {
      this.clearMessages();
      app.login.showConfigForm = true;
      app.login.loggedIn = true;
      app.loading.login = false;
      app.loading.reg = false;
      app.option_select = 'true';
      // Since we re showing config form, we check if we can already save chatbot token
      app.generateChatbotToken();
      if (!restoring) {
        // We only update chatbot token if we're not restoring session
        app.updateChatbotToken();
      }

    },
    updateSession: function (session) {
      if (typeof session === 'object') {
        session = JSON.stringify(session);
      }
      j.ajax({
        type: "POST",
        url: ajaxurl,
        data: {
          action: 'update_session',
          chatbot_session: session,
          update_session_nonce: jQuery('#update_session_nonce').val()
        }
      });
    },
    /**
     * @returns {boolean} Whether any of the values in the specified object is true.
     */
    anyMessage: function(msgObj) {
      return Object.values(msgObj).some(function(val) { return val; });
    },
    /**
     * Clears all messages and bootstrap alerts
     */
    clearMessages: function() {
      for (var key in app.messages) {
        if (app.messages.hasOwnProperty(key)) app.messages[key] = {};
      }
      app.alertDanger = false;
      app.alertSuccess = false;
    },
    /**
     * Unlinks cliengo account with WP site
     */
    unlinkAccount: function() {
      app.login.loggedIn = false;
      app.login.showConfigForm = false;
      app.login.disableWebsiteSelector = false;
      app.selectedWebsiteId = null;
      app.chatbot_token = null;
      app.messageChatbotPos = false;
      app.clearMessages();
      app.messages.loggedOut.success = true;
      // Clear session
      app.updateSession('');
      app.clearChatbotToken();
    },

    /**
     * Updates the chatbot token through the form
     * @param restoring - indicates whether we're just restoring information
     * (i.e. don't show installation successful message)
     */
    updateToken: function(restoring) {
      app.clearMessages();
      if (app.validateChatbotTokenField(app.chatbot_token)) {
        // Don't need to select a website when a specific token was provided
        app.login.disableWebsiteSelector = true;
        app.showConfigForm(restoring);
      } else {
        app.messages.token.tokenValidError = true;
        app.alertDanger  = true;
      }
    },
    /**
     * Perform chatbot installation on site and persist website selection
     */
    selectWebsite: function() {
      // Perform installation
      app.generateChatbotToken();
      app.updateChatbotToken();
      // Persist session
      var session = app.company;
      session.selectedWebsiteId = app.selectedWebsiteId;
      // stop highlighting chatbot selection section
      app.stopHighlightAnimation();
      app.updateSession(session);
    },
    /**
     * Closes the installation successful modal
     */
    closeModal: function() {
      app.showModal = false;
    },
    /**
     * Prevents click event from propagation
     * @param e
     */
    prevent: function (e) {
      e.stopPropagation();
    },
    /**
     * Begins highlighting the chatbot selection section
     */
    beginHighlightAnimation: function() {
      app.highlight = true;
    },
    /**
     * Stops highlighting the chatbot selection section
     */
    stopHighlightAnimation: function() {
      app.highlight = false;
    },
    /**
     * Toggle password visibility
     */
    togglePassword: function(key) {
      app.showPasswords[key] = !app.showPasswords[key];
    },
    restoreForms: function() {
      this.clearMessages();
    }
  }
});

Vue.directive('init', {
  bind: function(el, binding, vnode) {
    vnode.context[binding.arg] = binding.value;
  }
});

/**
 * Checks for error in registration form data and returns the appropriate
 * error msg key if errors were found
 * @param regData
 * @returns {string}
 */
function checkForErrors(regData) {
  var errors = null;
  // See if all fields exist. If they do, test website url
  if (regData.name && regData.email && regData.password && regData.website) {
    if (regData.password.length < 8) {
      errors = "shortPassword";
    }
    if (!/^(https?:\/\/)?[a-z0-9-]+(\.[a-z0-9-]+)+\/?[a-z0-9-]*$/i.test(regData.website)) {
      errors = "invalidWebsite";
    }
  } else {
    errors = "missingInfo";
  }
  return errors;
}

/**
 * Appends http if not specified
 * @param sourceName
 * @returns {string}
 */
function sanitizeUrl (sourceName) {
  if (!sourceName.startsWith("http://") && !sourceName.startsWith("https://")) {
    sourceName = "http://" + sourceName;
  }
  return sourceName;
}