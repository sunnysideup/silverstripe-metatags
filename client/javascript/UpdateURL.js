Behaviour.register(
  {
    'input#Form_EditForm_Title': {
      /**
      * Get the URL segment to suggest a new field
      */
      onchange: function () {
        if (this.value.length == 0) return
        if (!$('Form_EditForm_URLSegment')) return
        var urlSegmentField = $('Form_EditForm_URLSegment')
        var newSuggestion = urlSegmentField.suggestNewValue(this.value.toLowerCase())
        var isNew = urlSegmentField.value.indexOf('new') == 0
        urlSegmentField.value = newSuggestion
      }
    }
  }
)
