<footer class="footer py-3 mt-5">
    <div class="container text-center">
        <p class="mb-0 small text-white">
            © <?php echo date("Y"); ?> A2Z Engineering (pvt) Ltd. All rights reserved.
        </p>
    </div>
     <style>
     .footer {
         background-color: #525357; /* Dark Gray background */
          box-shadow: 0 -2px 4px rgba(0, 0, 0, 0.1);
           margin-top: auto; /* Pushes the footer to the bottom*/
           border-top: 1px solid #666; /* Thin line at the top */
      }

        .container {
             max-width: 1200px;
             padding: 0 20px; /* Added padding to container for small screens*/
             margin: 0 auto;
        }

       .footer .text-white {
        color: #fff;
        font-size: .9rem;
      }

        @media (max-width: 768px) {
          .footer .container {
             padding: 0 15px;
          }
           .footer .text-white {
            font-size: .8rem;
         }
      }
   </style>
</footer>
