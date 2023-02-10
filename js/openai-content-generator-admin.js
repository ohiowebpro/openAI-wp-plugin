window.onload = function() {

    const openaiContentGenerator = {
        nonce: 'security'
    };
    document.getElementById('generate-content-button').addEventListener('click', function(event) {
        event.preventDefault();
        var clientId = wp.data.select('core/block-editor').getSelectedBlockClientId();
        var thisButton = this;
        // Add a loading spinner while the content is being generated
        thisButton.innerHTML = '<span class="spinner is-active"></span> Generating...';
        // Retrieve the block data
        var blockData = wp.data.select('core/block-editor').getBlock(clientId).attributes;

        // Send the block data to the WordPress backend
        var xhr = new XMLHttpRequest();
        xhr.open('POST', ajaxurl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                // Replace the block with the generated content
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    var content = response.data;
                    var blocks = [];
                    content.forEach(function (text) {
                        blocks.push({
                            blockName: 'core/paragraph',
                            attributes: {
                                content: text
                            }
                        });
                    });
                    wp.data.dispatch('core/block-editor').replaceBlocks(clientId, blocks);
                }
                // Remove the loading spinner
                thisButton.innerHTML = 'Generate Content';
            }
        };
        xhr.send(encodeURI('action=openai_content_generator_generate_content&blockData=' + JSON.stringify(blockData)));
    });

};







// ( function( $ ) {
//     jQuery('#generate-content-button').on('click', function(event) {
//         event.preventDefault();
//         alert('button test');
//         var thisButton = $(this);
//         // Add a loading spinner while the content is being generated
//         thisButton.html('<span class="spinner is-active"></span> Generating...');
//
//         // Retrieve the selected block's client ID
//         var clientId = wp.data.select('core/block-editor').getSelectedBlockClientId();
//
//         // Retrieve the block data
//         var blockData = wp.data.select('core/block-editor').getBlock(clientId).attributes;
//
//         // Send the block data to the WordPress backend
//         $.ajax({
//             url: ajaxurl,
//             type: 'POST',
//             data: {
//                 action: 'openai_content_generator_generate_content',
//                 security: openaiContentGenerator.nonce,
//                 blockData: blockData
//             },
//             success: function (response) {
//                 // Replace the block with the generated content
//                 if (response.success) {
//                     var content = response.data;
//                     var blocks = [];
//                     content.forEach(function (text) {
//                         blocks.push({
//                             blockName: 'core/paragraph',
//                             attributes: {
//                                 content: text
//                             }
//                         });
//                     });
//                     wp.data.dispatch('core/block-editor').replaceBlocks(clientId, blocks);
//                 }
//                 // Remove the loading spinner
//                 thisButton.html('Generate Content');
//             }
//         });
//     });
// } )( jQuery );
